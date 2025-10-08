<?php

namespace App\Controllers;

use App\Models\FormModel;
use App\Models\DbpanelModel;
use App\Models\FormSubmissionModel;
use App\Models\FormSubmissionDataModel;
use App\Models\UserModel;
use App\Models\DepartmentModel;
use PhpOffice\PhpWord\TemplateProcessor;

class PdfGenerator extends BaseController
{
    protected $formModel;
    protected $dbpanelModel;
    protected $formSubmissionModel;
    protected $formSubmissionDataModel;
    protected $userModel;
    protected $departmentModel;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->formModel = new FormModel();
        $this->dbpanelModel = new DbpanelModel();
        $this->formSubmissionModel = new FormSubmissionModel();
        $this->formSubmissionDataModel = new FormSubmissionDataModel();
        $this->userModel = new UserModel();
        $this->departmentModel = new DepartmentModel();
    }
    
    public function generateFormPdf($submissionId, $outputFormat = 'docx')
    {
        // Increase limits for large file generation
        @ini_set('memory_limit', '256M');
        @ini_set('max_execution_time', '300'); // 5 minutes
        
        // Ensure session is active - critical for downloads
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        if (!$userId) {
            log_message('error', 'Export attempt without valid session for submission: ' . $submissionId);
            return $this->response
                ->setStatusCode(401)
                ->setJSON(['error' => 'Session expired. Please login again.']);
        }
        
        $submission = $this->formSubmissionModel->find($submissionId);
        
        if (!$submission) {
            log_message('error', 'Export attempted for non-existent submission: ' . $submissionId);
            return $this->response
                ->setStatusCode(404)
                ->setJSON(['error' => 'Submission not found']);
        }
        
        // Check permissions - allow export for completed forms
        $canExport = false;
        
        if ($userType === 'admin' || $userType === 'approving_authority' || $userType === 'service_staff') {
            $canExport = ($submission['status'] == 'completed');
        } else if ($userType === 'requestor' && $submission['submitted_by'] == $userId) {
            $canExport = ($submission['status'] == 'completed');
        }
        
        if (!$canExport) {
            log_message('warning', 'Export permission denied for user ' . $userId . ' on submission ' . $submissionId);
            return $this->response
                ->setStatusCode(403)
                ->setJSON(['error' => 'You don\'t have permission to export this submission or it is not completed']);
        }
        
        // Get form details (include office & department names via manual joins for placeholders)
        $form = $this->formModel->find($submission['form_id']);
        $officeName = '';
        $officeCode = '';
        if (!empty($form['office_id'])) {
            $officeRow = $this->db->table('offices')->select('code, description')->where('id', $form['office_id'])->get()->getRowArray();
            if ($officeRow) { $officeCode = $officeRow['code']; $officeName = $officeRow['description']; }
        }
        
        // Get panel fields
        $panelFields = $this->dbpanelModel->getPanelFields($submission['panel_name']);
        
        // Get submission data
        $submissionData = $this->formSubmissionDataModel->getSubmissionDataAsArray($submissionId);
        
        // Get user details
        $requestor = $this->userModel->find($submission['submitted_by']);
        $approver = !empty($submission['approver_id']) ? $this->userModel->find($submission['approver_id']) : null;
        $serviceStaff = !empty($submission['service_staff_id']) ? $this->userModel->find($submission['service_staff_id']) : null;
        
        // Get department information
        $requestorDept = '';
        if (!empty($requestor['department_id'])) {
            $department = $this->departmentModel->find($requestor['department_id']);
            $requestorDept = $department ? $department['description'] : '';
        }
        
        // Prepare placeholders and values
        $placeholderValues = $this->prepareTextPlaceholders(
            $form,
            $submission,
            $submissionId,
            $requestor,
            $requestorDept,
            $approver,
            $serviceStaff,
            $panelFields,
            $submissionData,
            $officeCode,
            $officeName
        );
        
        // Determine which template to use
        $templatePath = FCPATH . 'templates/docx/' . $form['code'] . '_template.docx';
        if (!file_exists($templatePath)) {
            // Fall back to default template
            $templatePath = FCPATH . 'templates/docx/default_form_template.docx';
            
            // If no default exists either, return error
            if (!file_exists($templatePath)) {
                log_message('error', 'DOCX template not found for form: ' . $form['code']);
                return $this->response
                    ->setStatusCode(500)
                    ->setJSON(['error' => 'DOCX template not found']);
            }
        }
        
    // Determine desired output format: priority (explicit param) > query ?format=
    $reqFormat = strtolower($this->request->getGet('format') ?? $outputFormat ?? 'docx');
    if ($reqFormat === 'word') { $reqFormat = 'docx'; }
    if (!in_array($reqFormat, ['docx','pdf'], true)) { $reqFormat = 'docx'; }

        try {
            // Load the Word template (base for both docx & pdf)
            $templateProcessor = new TemplateProcessor($templatePath);
            
            // Replace all text placeholders using any variables present in the template
            // This ensures placeholders are removed even if the uploaded template contains extra variables
            try {
                $templateVars = $templateProcessor->getVariables();
            } catch (\Exception $e) {
                $templateVars = [];
            }

            foreach ($templateVars as $var) {
                $upperVar = strtoupper($var);
                // Handle P_ signature image placeholders specially
                if (in_array($upperVar, ['P_REQUESTOR_SIGNATURE','P_APPROVER_SIGNATURE','P_SERVICE_STAFF_SIGNATURE'])) {
                    // We'll attempt to set the image if available below after loop; skip normal value substitution here
                    continue;
                }
                $placeholderKey = '{{' . $var . '}}';
                $value = $placeholderValues[$placeholderKey] ?? '';
                try { $templateProcessor->setValue($var, $value); }
                catch (\Exception $e) { log_message('warning','Failed to set template variable '.$var.': '.$e->getMessage()); }
            }
            
            // Add signature images
            $this->addSignatureImages($templateProcessor, $requestor, $approver, $serviceStaff);

            // Additionally map new P_ prefixed placeholders to the same images if those tags exist in template
            $this->applyPrefixedSignaturePlaceholders($templateProcessor, $requestor, $approver, $serviceStaff);
            
            // Create a temporary directory if it doesn't exist
            $tempDir = WRITEPATH . 'temp/';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Clean up old temp files (older than 1 hour) to prevent disk space issues
            $this->cleanupOldTempFiles($tempDir, 3600);
            
            // Save the processed document to a temp file
            $tempDocxFile = $tempDir . uniqid('form_') . '.docx';
            $templateProcessor->saveAs($tempDocxFile);
            
            // Generate a unique filename for the Word document
            $docxFilename = 'Form_' . $form['code'] . '_Submission_' . $submissionId . '.docx';
            
            // After initial save, fill any Word Content Controls (structured document tags) using field names, labels, and placeholder map
            $this->fillContentControls($tempDocxFile, $panelFields, $submissionData, $placeholderValues);

            // Inject signature images into picture content controls with tags P_REQUESTOR_SIGNATURE, etc.
            $this->injectSignaturePictureControls($tempDocxFile, $requestor, $approver, $serviceStaff);

            if ($reqFormat === 'pdf') {
                // Convert DOCX to PDF via iLovePDF when API keys configured
                // If conversion fails or not configured, fallback to DOCX
                $pdfFile = $tempDir . uniqid('form_pdf_') . '.pdf';
                $converted = false;
                try {
                    $publicKey = getenv('ILOVEPDF_PUBLIC_KEY');
                    $secretKey = getenv('ILOVEPDF_SECRET_KEY');
                    if ($publicKey && $secretKey) {
                        // Check if iLovePDF library is available
                        if (class_exists('Ilovepdf\Ilovepdf')) {
                            log_message('info', 'Starting PDF conversion using iLovePDF for submission: ' . $submissionId);
                            $ilovepdf = new \Ilovepdf\Ilovepdf($publicKey, $secretKey);
                            $task = $ilovepdf->newTask('officepdf');
                            $file = $task->addFile($tempDocxFile);
                            $task->execute();
                            $task->download($tempDir);
                            
                            // Find the converted PDF file (iLovePDF saves with a specific naming pattern)
                            // Look for the newest PDF file created after the DOCX file
                            $pdfFiles = glob($tempDir . '*.pdf');
                            if (!empty($pdfFiles)) {
                                // Sort by modification time (newest first)
                                usort($pdfFiles, function($a, $b) {
                                    return filemtime($b) - filemtime($a);
                                });
                                
                                // Get the newest PDF file
                                foreach ($pdfFiles as $candidate) {
                                    if (filemtime($candidate) >= filemtime($tempDocxFile)) {
                                        @rename($candidate, $pdfFile);
                                        $converted = true;
                                        log_message('info', 'PDF conversion successful for submission: ' . $submissionId);
                                        break;
                                    }
                                }
                            }
                        } else {
                            log_message('warning', 'iLovePDF library not found. Install via: composer require ilovepdf/ilovepdf-php');
                        }
                    } else {
                        log_message('warning', 'iLovePDF API keys not configured in .env file');
                    }
                } catch (\Throwable $e) {
                    log_message('error','PDF conversion failed for submission ' . $submissionId . ': '.$e->getMessage());
                }
                
                if ($converted && file_exists($pdfFile)) {
                    $pdfFilename = str_replace('.docx','.pdf',$docxFilename);
                    
                    // Verify file exists and is readable
                    if (!is_readable($pdfFile)) {
                        log_message('error', 'PDF file not readable: ' . $pdfFile);
                        return $this->response
                            ->setStatusCode(500)
                            ->setJSON(['error' => 'Generated file is not readable']);
                    }
                    
                    // Clear any output buffers to prevent corruption
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    
                    // On Windows, ensure file is not locked by waiting briefly
                    clearstatcache(true, $pdfFile);
                    usleep(100000); // 100ms delay to ensure file write is complete
                    
                    // Verify file one more time
                    if (!file_exists($pdfFile) || !is_readable($pdfFile)) {
                        log_message('error', 'PDF file became unavailable before download: ' . $pdfFile);
                        http_response_code(500);
                        echo json_encode(['error' => 'File is not available']);
                        exit;
                    }
                    
                    // Get file info
                    $fileSize = filesize($pdfFile);
                    $mimeType = 'application/pdf';
                    
                    // Clear all headers
                    header_remove();
                    
                    // Set headers directly (bypass CodeIgniter response filtering)
                    header('Content-Type: ' . $mimeType);
                    header('Content-Disposition: attachment; filename="' . $pdfFilename . '"');
                    header('Content-Length: ' . $fileSize);
                    header('Content-Transfer-Encoding: binary');
                    header('Accept-Ranges: bytes');
                    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                    header('Pragma: public');
                    header('Expires: 0');
                    
                    // Disable execution time limit for large files
                    @set_time_limit(0);
                    
                    // Read and output file in chunks to handle large files
                    $chunkSize = 1024 * 8; // 8KB chunks
                    $handle = @fopen($pdfFile, 'rb');
                    
                    if ($handle === false) {
                        log_message('error', 'Failed to open PDF file for reading: ' . $pdfFile);
                        http_response_code(500);
                        echo json_encode(['error' => 'Failed to read file']);
                        exit;
                    }
                    
                    // Send file in chunks
                    while (!feof($handle)) {
                        $buffer = fread($handle, $chunkSize);
                        if ($buffer === false) {
                            break;
                        }
                        echo $buffer;
                        
                        // Force immediate output (important for Windows)
                        if (ob_get_level()) {
                            ob_flush();
                        }
                        flush();
                    }
                    
                    fclose($handle);
                    exit; // Terminate script after download
                } else {
                    // Fallback to DOCX if PDF conversion failed
                    log_message('warning', 'PDF conversion failed, returning DOCX instead for submission: ' . $submissionId);
                }
            }
            
            // Verify DOCX file exists and is readable before download
            if (!file_exists($tempDocxFile)) {
                log_message('error', 'Generated DOCX file not found: ' . $tempDocxFile);
                return $this->response
                    ->setStatusCode(500)
                    ->setJSON(['error' => 'Generated file not found']);
            }
            
            if (!is_readable($tempDocxFile)) {
                log_message('error', 'Generated DOCX file not readable: ' . $tempDocxFile);
                return $this->response
                    ->setStatusCode(500)
                    ->setJSON(['error' => 'Generated file is not readable']);
            }
            
            // Clear any output buffers to prevent corruption
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // On Windows, ensure file is not locked by waiting briefly
            clearstatcache(true, $tempDocxFile);
            usleep(100000); // 100ms delay to ensure file write is complete
            
            // Verify file one more time
            if (!file_exists($tempDocxFile) || !is_readable($tempDocxFile)) {
                log_message('error', 'File became unavailable before download: ' . $tempDocxFile);
                http_response_code(500);
                echo json_encode(['error' => 'File is not available']);
                exit;
            }
            
            // Get file info
            $fileSize = filesize($tempDocxFile);
            $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            
            // Clear all headers
            header_remove();
            
            // Set headers directly (bypass CodeIgniter response filtering)
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . $docxFilename . '"');
            header('Content-Length: ' . $fileSize);
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
            header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
            header('Pragma: public');
            header('Expires: 0');
            
            // Disable execution time limit for large files
            @set_time_limit(0);
            
            // Read and output file in chunks to handle large files
            $chunkSize = 1024 * 8; // 8KB chunks
            $handle = @fopen($tempDocxFile, 'rb');
            
            if ($handle === false) {
                log_message('error', 'Failed to open file for reading: ' . $tempDocxFile);
                http_response_code(500);
                echo json_encode(['error' => 'Failed to read file']);
                exit;
            }
            
            // Send file in chunks
            while (!feof($handle)) {
                $buffer = fread($handle, $chunkSize);
                if ($buffer === false) {
                    break;
                }
                echo $buffer;
                
                // Force immediate output (important for Windows)
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }
            
            fclose($handle);
            exit; // Terminate script after download
            
        } catch (\Exception $e) {
            log_message('error', 'Document generation error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return $this->response
                ->setStatusCode(500)
                ->setJSON(['error' => 'Error generating document: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Prepare all text placeholder values from the form data
     */
    private function prepareTextPlaceholders($form, $submission, $submissionId, $requestor, $requestorDept, $approver, $serviceStaff, $panelFields, $submissionData, $officeCode = '', $officeName = '')
    {
        $placeholders = [
            // Basic form information
            '{{FORM_ID}}' => $form['id'],
            '{{FORM_CODE}}' => $form['code'],
            '{{FORM_DESCRIPTION}}' => $form['description'],
            '{{FORM_STATUS}}' => ucfirst($submission['status']),
            '{{OFFICE_CODE}}' => $officeCode,
            '{{OFFICE_NAME}}' => $officeName,
            
            // Submission information
            '{{SUBMISSION_ID}}' => $submissionId,
            '{{SUBMISSION_DATE}}' => date('M d, Y', strtotime($submission['created_at'])),
            '{{PANEL_NAME}}' => $submission['panel_name'],
            
            // Requestor information
            '{{REQUESTOR_ID}}' => $requestor['id'],
            '{{REQUESTOR_NAME}}' => $requestor['full_name'],
            '{{REQUESTOR_EMAIL}}' => $requestor['email'] ?? '',
            '{{REQUESTOR_DEPARTMENT}}' => $requestorDept,
            '{{REQUESTOR_SIGN_DATE}}' => !empty($submission['requestor_signature_date']) ? 
                date('M d, Y', strtotime($submission['requestor_signature_date'])) : '',
            
            // Dates and meta information
            '{{GENERATED_DATE}}' => date('M d, Y'),
            '{{GENERATED_BY}}' => session()->get('full_name') ?? 'System',
            '{{PAGE}}' => '',  // Will be handled by Word
            '{{TOTAL_PAGES}}' => '',  // Will be handled by Word
        ];
        
        // Add approver information if available
        if ($approver) {
            $placeholders['{{APPROVER_ID}}'] = $approver['id'];
            $placeholders['{{APPROVER_NAME}}'] = $approver['full_name'];
            $placeholders['{{APPROVER_EMAIL}}'] = $approver['email'] ?? '';
            $placeholders['{{APPROVAL_DATE}}'] = !empty($submission['approved_at']) ? 
                date('M d, Y', strtotime($submission['approved_at'])) : '';
            // Unified explicit approver signature date placeholder (falls back to approved_at)
            $approverSignSource = $submission['approver_signature_date'] ?? $submission['approved_at'] ?? null;
            $placeholders['{{APPROVER_SIGN_DATE}}'] = $approverSignSource ? date('M d, Y', strtotime($approverSignSource)) : '';
            $placeholders['{{APPROVAL_COMMENTS}}'] = $submission['approval_comments'] ?? '';
        } else {
            $placeholders['{{APPROVER_ID}}'] = '';
            $placeholders['{{APPROVER_NAME}}'] = '';
            $placeholders['{{APPROVER_EMAIL}}'] = '';
            $placeholders['{{APPROVAL_DATE}}'] = '';
            $placeholders['{{APPROVER_SIGN_DATE}}'] = '';
            $placeholders['{{APPROVAL_COMMENTS}}'] = '';
        }
        
        // Add rejection reason if applicable
        if ($submission['status'] === 'rejected') {
            $placeholders['{{REJECTED_REASON}}'] = $submission['rejected_reason'] ?? 
                $submission['rejection_reason'] ?? '';
        } else {
            $placeholders['{{REJECTED_REASON}}'] = '';
        }
        
        // Add service staff information if available
        if ($serviceStaff) {
            $placeholders['{{SERVICE_STAFF_ID}}'] = $serviceStaff['id'];
            $placeholders['{{SERVICE_STAFF_NAME}}'] = $serviceStaff['full_name'];
            $placeholders['{{SERVICE_STAFF_EMAIL}}'] = $serviceStaff['email'] ?? '';
            $placeholders['{{SERVICE_DATE}}'] = !empty($submission['service_staff_signature_date']) ? 
                date('M d, Y', strtotime($submission['service_staff_signature_date'])) : '';
            $placeholders['{{SERVICE_NOTES}}'] = $submission['service_notes'] ?? '';
        } else {
            $placeholders['{{SERVICE_STAFF_ID}}'] = '';
            $placeholders['{{SERVICE_STAFF_NAME}}'] = '';
            $placeholders['{{SERVICE_STAFF_EMAIL}}'] = '';
            $placeholders['{{SERVICE_DATE}}'] = '';
            $placeholders['{{SERVICE_NOTES}}'] = '';
        }
        
        // Helper: convert 0-based index to letters (A, B, ... Z, AA, AB ...)
        $indexToLetters = function($n) {
            $s = '';
            $n = (int)$n;
            while ($n >= 0) {
                $s = chr(65 + ($n % 26)) . $s;
                $n = intval($n / 26) - 1;
            }
            return $s;
        };

        // Add form fields to placeholders
        $checkboxFieldCounter = 0; // used to assign A_, B_, C_ prefixes
        // Use helper functions to render human-friendly values for placeholders
        foreach ($panelFields as $field) {
            $fieldName = $field['field_name'];
            // Prefer labeled display for selectable fields, fallback to raw value for simple fields
            try {
                // The helper is autoloaded; call the raw version (no escaping) for templates
                $display = function_exists('render_field_display_raw') ? render_field_display_raw($field, $submissionData) : (isset($submissionData[$fieldName]) ? $submissionData[$fieldName] : '');
            } catch (\Throwable $e) {
                $display = isset($submissionData[$fieldName]) ? $submissionData[$fieldName] : '';
            }
            $placeholders['{{' . strtoupper($fieldName) . '}}'] = $display;
        }

        // Also provide short placeholders for DOCX templates using ${F_fieldname} and ${B_fieldname}
        // ${F_fieldname} -> single selected value
        // ${B_fieldname} -> compact radio block: selected marked with '◉', unselected left blank
    foreach ($panelFields as $field) {
            $fieldName = $field['field_name'];
            $fieldKeyF = '{{F_' . $fieldName . '}}';
            $fieldKeyB = '{{B_' . $fieldName . '}}';

            // Use helpers to get raw/unescaped values for template placeholders
            $rawValue = isset($submissionData[$fieldName]) ? $submissionData[$fieldName] : '';
            $displayF = function_exists('render_field_display_raw') ? render_field_display_raw($field, $submissionData) : $rawValue;

            // Normalize stored values: may be JSON array (checkboxes) or scalar
            $selectedValues = [];
            if (is_string($rawValue)) {
                $decoded = json_decode($rawValue, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $selectedValues = $decoded;
                } else {
                    $selectedValues = [$rawValue];
                }
            } elseif (is_array($rawValue)) {
                $selectedValues = $rawValue;
            } else {
                $selectedValues = [$rawValue];
            }

            // Prepare a lower-cased version for case-insensitive comparisons
            $selectedValuesLower = array_map(function($v) {
                return mb_strtolower((string)$v);
            }, $selectedValues);

            // Resolve options (prefer explicit options array, else try JSON in default_value, else newline-split)
            $opts = [];
            if (!empty($field['options']) && is_array($field['options'])) {
                $opts = $field['options'];
            } elseif (!empty($field['default_value'])) {
                $decoded = json_decode($field['default_value'], true);
                if (is_array($decoded) && !empty($decoded)) {
                    $opts = $decoded;
                } else {
                    $opts = array_filter(array_map('trim', explode("\n", $field['default_value'])));
                }
            }

            // F placeholder: first selected value or empty — prefer labeled display
            $placeholders[$fieldKeyF] = !empty($selectedValues) ? (string)$selectedValues[0] : '';

            // B placeholder: compact block of options; selected marked with '◉', others blank marker
            if (empty($opts)) {
                // If no options known, use displayF (which may join values) or join selected values
                $placeholders[$fieldKeyB] = $displayF !== '' ? $displayF : (!empty($selectedValues) ? implode(', ', $selectedValues) : '');
            } else {
                $lines = [];
                foreach ($opts as $opt) {
                    // Normalize option to a scalar string for comparisons (supports array structures)
                    if (is_array($opt)) {
                        $optCompare = mb_strtolower((string)($opt['sub_field'] ?? $opt['label'] ?? reset($opt) ?? '')); 
                        $optLabelOut = (string)($opt['label'] ?? $opt['sub_field'] ?? $optCompare);
                    } else {
                        $optCompare = mb_strtolower((string)$opt);
                        $optLabelOut = (string)$opt;
                    }
                    $marker = in_array($optCompare, $selectedValuesLower, true) ? '◉ ' : '';
                    $lines[] = $marker . $optLabelOut;
                }
                $placeholders[$fieldKeyB] = implode("\n", $lines);
            }

            // NEW: C_ placeholder -> inline checkbox representation e.g. "☑Yes  ☐No"
            if (!empty($opts)) {
                $checkboxPieces = [];
                foreach ($opts as $opt) {
                    if (is_array($opt)) {
                        $optCompare = mb_strtolower((string)($opt['sub_field'] ?? $opt['label'] ?? reset($opt) ?? ''));
                        $optLabelOut = (string)($opt['label'] ?? $opt['sub_field'] ?? $optCompare);
                    } else {
                        $optCompare = mb_strtolower((string)$opt);
                        $optLabelOut = (string)$opt;
                    }
                    $isSel = in_array($optCompare, $selectedValuesLower, true);
                    $checkboxPieces[] = ($isSel ? '☑' : '☐') . $optLabelOut;
                }
                $placeholders['{{C_' . $fieldName . '}}'] = implode('  ', $checkboxPieces); // double space between groups
            }

            // Legacy: numeric per-option placeholders: {{A_fieldname_1}}, {{A_fieldname_2}}, ...
            foreach ($opts as $idx => $opt) {
                $legacyKey = '{{A_' . $fieldName . '_' . ($idx + 1) . '}}';
                if (is_array($opt)) {
                    $optCompare = mb_strtolower((string)($opt['sub_field'] ?? $opt['label'] ?? reset($opt) ?? ''));
                    $optLabelOut = (string)($opt['label'] ?? $opt['sub_field'] ?? $optCompare);
                } else {
                    $optCompare = mb_strtolower((string)$opt);
                    $optLabelOut = (string)$opt;
                }
                $placeholders[$legacyKey] = in_array($optCompare, $selectedValuesLower, true) ? $optLabelOut : '';
            }

            // New standard: option tag = FIELDNAME_OPTIONNAME (both uppercased, OPTIONNAME slugged)
            // Example: field_name = priority_level, option "High Impact" => {{PRIORITY_LEVEL_HIGH_IMPACT}}
            foreach ($opts as $opt) {
                $optLabel = '';
                $optValue = '';
                $slugSource = '';
                $candidateValuesLower = [];

                if (is_array($opt)) {
                    // Support structures like { label: 'Lighting', sub_field: 'LIGHTINGS' }
                    $optLabel = (string)($opt['label'] ?? $opt['sub_field'] ?? '');
                    $optValue = (string)($opt['sub_field'] ?? $optLabel);
                    $slugSource = (string)($opt['sub_field'] ?? $optLabel);
                    $candidateValuesLower = array_unique(array_filter([
                        mb_strtolower($optLabel),
                        mb_strtolower($optValue)
                    ]));
                } else {
                    $optLabel = (string)$opt;
                    $optValue = $optLabel;
                    $slugSource = $optLabel;
                    $candidateValuesLower = [mb_strtolower($optLabel)];
                }

                $slug = strtoupper($slugSource);
                $slug = preg_replace('/[^A-Z0-9]+/u', '_', $slug); // non-alnum -> underscore
                $slug = trim($slug, '_');
                if ($slug === '') continue; // skip empty

                $optPlaceholder = '{{' . strtoupper($fieldName) . '_' . $slug . '}}';
                $isSelected = false;
                foreach ($candidateValuesLower as $cand) {
                    if (in_array($cand, $selectedValuesLower, true)) { $isSelected = true; break; }
                }
                $placeholders[$optPlaceholder] = $isSelected ? $optLabel : '';

                // NEW: Per-option content-control friendly checkbox symbol placeholder
                // Tag format: C_FIELDNAME_OPTION (e.g. C_UNDER_WARRANTY_YES)
                // Value: ☑ if selected else ☐
                $checkboxSymbolKey = '{{C_' . strtoupper($fieldName) . '_' . $slug . '}}';
                $placeholders[$checkboxSymbolKey] = $isSelected ? '☑' : '☐';
            }

            // If this is a checkbox-like field with options, also add short lettered aliases
            if (!empty($opts)) {
                $prefix = $indexToLetters($checkboxFieldCounter); // 0 -> A, 1 -> B, etc.
                foreach ($opts as $idx => $opt) {
                    if (is_array($opt)) {
                        $optCompare = mb_strtolower((string)($opt['sub_field'] ?? $opt['label'] ?? reset($opt) ?? ''));
                    } else {
                        $optCompare = mb_strtolower((string)$opt);
                    }
                    $shortKey = '{{' . $prefix . '_' . ($idx + 1) . '}}';
                    $placeholders[$shortKey] = in_array($optCompare, $selectedValuesLower, true) ? '◉ ' : '';
                }
                $checkboxFieldCounter++;
            }
        }
        
        // Add completion date
        if (!empty($submission['completed_date'])) {
            $placeholders['{{COMPLETED_DATE}}'] = date('M d, Y', strtotime($submission['completed_date']));
        } else if (!empty($submission['completed_at'])) {
            $placeholders['{{COMPLETED_DATE}}'] = date('M d, Y', strtotime($submission['completed_at']));
        } else {
            $placeholders['{{COMPLETED_DATE}}'] = '';
        }
        
        return $placeholders;
    }
    
    /**
     * Add signature images to the template
     */
    private function addSignatureImages($templateProcessor, $requestor, $approver, $serviceStaff)
    {
        // Add requestor signature if available
        if (!empty($requestor['signature'])) {
            $signaturePath = strpos($requestor['signature'], 'uploads/signatures/') === 0 
                ? FCPATH . $requestor['signature'] 
                : FCPATH . 'uploads/signatures/' . $requestor['signature'];
            
            if (file_exists($signaturePath)) {
                log_message('info', 'Requestor signature image found: ' . $requestor['signature'] . ' for user ID: ' . $requestor['id']);
                try {
                    // Set a small, fixed display size for signature images (width x height in px)
                    $templateProcessor->setImageValue('REQUESTOR_SIGNATURE', [
                        'path' => $signaturePath,
                        'width' => 200,
                        'height' => 60
                    ]);
                } catch (\Exception $e) {
                    log_message('error', 'Failed to add requestor signature: ' . $e->getMessage());
                }
            } else {
                log_message('warning', 'Requestor signature file not found: ' . $signaturePath);
            }
        }
        
        // Add approver signature if available
        if ($approver && !empty($approver['signature'])) {
            $signaturePath = strpos($approver['signature'], 'uploads/signatures/') === 0 
                ? FCPATH . $approver['signature'] 
                : FCPATH . 'uploads/signatures/' . $approver['signature'];
            
            if (file_exists($signaturePath)) {
                log_message('info', 'Approver signature image found: ' . $approver['signature'] . ' for user ID: ' . $approver['id']);
                try {
                    // Set a small, fixed display size for signature images
                    $templateProcessor->setImageValue('APPROVER_SIGNATURE', [
                        'path' => $signaturePath,
                        'width' => 200,
                        'height' => 60
                    ]);
                } catch (\Exception $e) {
                    log_message('error', 'Failed to add approver signature: ' . $e->getMessage());
                }
            } else {
                log_message('warning', 'Approver signature file not found: ' . $signaturePath);
            }
        }
        
        // Add service staff signature if available
        if ($serviceStaff && !empty($serviceStaff['signature'])) {
            $signaturePath = strpos($serviceStaff['signature'], 'uploads/signatures/') === 0 
                ? FCPATH . $serviceStaff['signature'] 
                : FCPATH . 'uploads/signatures/' . $serviceStaff['signature'];
            
            if (file_exists($signaturePath)) {
                log_message('info', 'Service staff signature image found: ' . $serviceStaff['signature'] . ' for user ID: ' . $serviceStaff['id']);
                try {
                    // Set a small, fixed display size for signature images
                    $templateProcessor->setImageValue('SERVICE_STAFF_SIGNATURE', [
                        'path' => $signaturePath,
                        'width' => 200,
                        'height' => 60
                    ]);
                } catch (\Exception $e) {
                    log_message('error', 'Failed to add service staff signature: ' . $e->getMessage());
                }
            } else {
                log_message('warning', 'Service staff signature file not found: ' . $signaturePath);
            }
        }
    }

    /**
     * Support new P_ signature placeholders (P_REQUESTOR_SIGNATURE, etc.) by injecting images
     * if those variables exist in the DOCX template.
     */
    private function applyPrefixedSignaturePlaceholders(TemplateProcessor $templateProcessor, $requestor, $approver, $serviceStaff)
    {
        $vars = [];
        try { $vars = $templateProcessor->getVariables(); } catch (\Throwable $e) { $vars = []; }
        if (empty($vars)) return;
        $varsUpper = array_map('strtoupper', $vars);

        $setImageIfExists = function($needle, $user) use ($varsUpper, $vars, $templateProcessor) {
            if (!in_array($needle, $varsUpper, true)) return; // not in template
            if (!$user || empty($user['signature'])) return;
            $signaturePath = strpos($user['signature'], 'uploads/signatures/') === 0
                ? FCPATH . $user['signature']
                : FCPATH . 'uploads/signatures/' . $user['signature'];
            if (!file_exists($signaturePath)) return;
            // Find original case variable name
            $index = array_search($needle, $varsUpper, true);
            $originalVar = $vars[$index];
            try { $templateProcessor->setImageValue($originalVar, $signaturePath); }
            catch (\Throwable $e) { log_message('warning','Failed P_ signature image for '.$originalVar.': '.$e->getMessage()); }
        };

        $setImageIfExists('P_REQUESTOR_SIGNATURE', $requestor);
        $setImageIfExists('P_APPROVER_SIGNATURE', $approver);
        $setImageIfExists('P_SERVICE_STAFF_SIGNATURE', $serviceStaff);
    }

    /**
     * Fill DOCX content controls (structured document tags) whose tag/alias matches a field_name.
     * This allows using Word content controls instead of ${VAR} placeholders.
     */
    private function fillContentControls(string $docxPath, array $panelFields, array $submissionData, array $placeholderValues): void
    {
        if (!is_file($docxPath)) return;
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) return;
        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) { $zip->close(); return; }
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        if (!@$doc->loadXML($xml)) { $zip->close(); return; }
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // Build map of canonical keys => values from placeholders first (covers system/meta fields)
        $valueMap = [];
        foreach ($placeholderValues as $pKey => $pVal) {
            // Placeholder keys like {{FIELD_NAME}} => FIELD_NAME
            $trimmed = trim($pKey, '{}');
            $canonical = $this->canonicalKey($trimmed);
            if (is_array($pVal)) { $pVal = implode(', ', $pVal); }
            $valueMap[$canonical] = (string)$pVal;
        }
        // Add panel field name + label variants
        foreach ($panelFields as $field) {
            $fname = $field['field_name'];
            $flabel = $field['field_label'] ?? '';
            $rawValue = isset($submissionData[$fname]) ? $submissionData[$fname] : '';
            try {
                $display = function_exists('render_field_display_raw') ? render_field_display_raw($field, $submissionData) : $rawValue;
            } catch (\Throwable $e) { $display = $rawValue; }
            if (is_array($display)) { $display = implode(', ', $display); }
            if (is_array($rawValue)) { $rawValue = implode(', ', $rawValue); }
            $valString = (string)$display;
            $valueMap[$this->canonicalKey($fname)] = $valString;
            if ($flabel) { $valueMap[$this->canonicalKey($flabel)] = $valString; }
        }

        // Derive checkbox/multi-select helpers for content control tags:
        // 1. C_fieldname => inline boxes for all options
        // 2. C_fieldname_option => single box (☑/☐) per option
        foreach ($panelFields as $field) {
            $fname = $field['field_name'];
            $raw = $submissionData[$fname] ?? '';
            // Decode selected values to array
            $selected = [];
            if (is_array($raw)) { $selected = $raw; }
            else {
                $dec = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) $selected = $dec; else if ($raw !== '' && $raw !== null) $selected = preg_split('/\s*[,;]\s*/', (string)$raw); 
            }
            $selectedLower = array_map(fn($v)=>mb_strtolower((string)$v), $selected);
            // Collect options similar to helper logic
            $opts = [];
            if (!empty($field['options']) && is_array($field['options'])) { $opts = $field['options']; }
            elseif (!empty($field['default_value'])) {
                $dec = json_decode($field['default_value'], true);
                if (is_array($dec) && !empty($dec)) $opts = $dec; else $opts = array_filter(array_map('trim', explode("\n", $field['default_value'])));
            }
            if (empty($opts)) continue; // nothing to build
            $inlinePieces = [];
            foreach ($opts as $opt) {
                if (is_array($opt)) { $optLabel = $opt['label'] ?? ($opt['sub_field'] ?? ''); $optValue = $opt['sub_field'] ?? ($opt['label'] ?? $optLabel); }
                else { $optLabel = (string)$opt; $optValue = $optLabel; }
                $isSel = in_array(mb_strtolower((string)$optValue), $selectedLower, true) || in_array(mb_strtolower((string)$optLabel), $selectedLower, true);
                $inlinePieces[] = ($isSel ? '☑' : '☐') . $optLabel;
                // per-option symbol tag: C_field_option
                $slug = preg_replace('/[^A-Za-z0-9]+/','_', strtoupper($optValue));
                $slug = trim($slug, '_');
                if ($slug !== '') {
                    $valueMap[$this->canonicalKey('C_' . $fname . '_' . $slug)] = $isSel ? '☑' : '☐';
                }
            }
            $valueMap[$this->canonicalKey('C_' . $fname)] = implode('  ', $inlinePieces);
        }

        $nodes = $xpath->query('//w:sdt');
        if (!$nodes) { $zip->close(); return; }
        $modified = false;
        $unmatched = [];
        foreach ($nodes as $sdt) {
            $tag = null;
            $tagNode = $xpath->query('.//w:tag', $sdt)->item(0);
            if ($tagNode && $tagNode->hasAttribute('w:val')) {
                $tag = trim($tagNode->getAttribute('w:val'));
            }
            if (!$tag) {
                $aliasNode = $xpath->query('.//w:alias', $sdt)->item(0);
                if ($aliasNode && $aliasNode->hasAttribute('w:val')) {
                    $tag = trim($aliasNode->getAttribute('w:val'));
                }
            }
            if (!$tag) continue;
            $canonical = $this->canonicalKey($tag);
            if (!array_key_exists($canonical, $valueMap)) { $unmatched[] = $tag; continue; }
            $value = $valueMap[$canonical];
            $contentNode = $xpath->query('.//w:sdtContent', $sdt)->item(0);
            if (!$contentNode) continue;
            $wNs = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

            // SAFER STRATEGY: Try to reuse existing w:t nodes so we do not destroy table/row structure inside complex content controls.
            $textNodes = $xpath->query('.//w:t', $contentNode);
            $lines = preg_split('/\r\n|\n|\r/', (string)$value);
            if (empty($lines)) { $lines = ['']; }

            if ($textNodes && $textNodes->length > 0) {
                // Put first line in first w:t, clear existing text
                /** @var \DOMElement $firstT */
                $firstT = $textNodes->item(0);
                while ($firstT->firstChild) { $firstT->removeChild($firstT->firstChild); }
                if (preg_match('/^\s|\s$/', $lines[0])) { $firstT->setAttribute('xml:space','preserve'); }
                $firstT->appendChild($doc->createTextNode($lines[0]));

                // Remove extra w:t nodes; we'll rebuild multi-line using new paragraphs appended at end of contentNode if needed
                for ($i=1; $i < $textNodes->length; $i++) {
                    $tn = $textNodes->item($i);
                    if ($tn && $tn->parentNode) { $tn->parentNode->removeChild($tn); }
                }
                // Additional lines -> append new paragraphs after existing structure (unless structure already has multiple paragraphs)
                if (count($lines) > 1) {
                    for ($i=1; $i < count($lines); $i++) {
                        $p = $doc->createElementNS($wNs, 'w:p');
                        $r = $doc->createElementNS($wNs, 'w:r');
                        $t = $doc->createElementNS($wNs, 'w:t');
                        if (preg_match('/^\s|\s$/', $lines[$i])) { $t->setAttribute('xml:space','preserve'); }
                        $t->appendChild($doc->createTextNode($lines[$i]));
                        $r->appendChild($t);
                        $p->appendChild($r);
                        $contentNode->appendChild($p);
                    }
                }
            } else {
                // Fallback: only clear if contentNode contains simple paragraphs; if it has table rows or tables leave structure intact.
                $hasTable = ($xpath->query('.//w:tbl', $contentNode)->length > 0) || ($xpath->query('./w:tr', $contentNode)->length > 0);
                if (!$hasTable) {
                    while ($contentNode->firstChild) { $contentNode->removeChild($contentNode->firstChild); }
                    foreach ($lines as $lineText) {
                        $p = $doc->createElementNS($wNs, 'w:p');
                        $r = $doc->createElementNS($wNs, 'w:r');
                        $t = $doc->createElementNS($wNs, 'w:t');
                        if (preg_match('/^\s|\s$/', $lineText)) { $t->setAttribute('xml:space','preserve'); }
                        $t->appendChild($doc->createTextNode($lineText));
                        $r->appendChild($t);
                        $p->appendChild($r);
                        $contentNode->appendChild($p);
                    }
                } else {
                    // If complex (table) structure, inject value into first cell's first paragraph to avoid corruption
                    $firstPara = $xpath->query('.//w:tc//w:p', $contentNode)->item(0);
                    if ($firstPara) {
                        // Remove existing runs in first paragraph
                        while ($firstPara->firstChild) { $firstPara->removeChild($firstPara->firstChild); }
                        $r = $doc->createElementNS($wNs, 'w:r');
                        $t = $doc->createElementNS($wNs, 'w:t');
                        if (preg_match('/^\s|\s$/', $lines[0])) { $t->setAttribute('xml:space','preserve'); }
                        $t->appendChild($doc->createTextNode($lines[0]));
                        $r->appendChild($t);
                        $firstPara->appendChild($r);
                    }
                }
            }
            $modified = true;
        }
        if ($modified) {
            $zip->addFromString('word/document.xml', $doc->saveXML());
        }
        $zip->close();
        if (!empty($unmatched)) {
            // Log unmatched tags to help template designers align tag names
            try { log_message('debug', 'DOCX content controls unmatched tags: '.json_encode($unmatched)); } catch (\Throwable $e) {}
        }
    }

    private function canonicalKey(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9_]+/','_', $name);
        return trim($name, '_');
    }

    /**
     * Post-process the DOCX to replace picture content controls tagged with P_* signature tags
     * (P_REQUESTOR_SIGNATURE, P_APPROVER_SIGNATURE, P_SERVICE_STAFF_SIGNATURE) with the actual
     * signature images if available. This is needed because TemplateProcessor only handles
     * variable-based image placeholders, not arbitrary picture content controls.
     */
    private function injectSignaturePictureControls(string $docxPath, $requestor, $approver, $serviceStaff): void
    {
        if (!is_file($docxPath)) return;
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) return;

        $documentXml = $zip->getFromName('word/document.xml');
        if ($documentXml === false) { $zip->close(); return; }
        $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
        if ($relsXml === false) { $relsXml = '<?xml version="1.0" encoding="UTF-8"?>\n<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships" />'; }

        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false; $doc->formatOutput = false;
        if (!@$doc->loadXML($documentXml)) { $zip->close(); return; }
        $relsDoc = new \DOMDocument(); $relsDoc->preserveWhiteSpace=false; $relsDoc->formatOutput=false;
        if (!@$relsDoc->loadXML($relsXml)) { $zip->close(); return; }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('w','http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // Collect existing relationship IDs to avoid collision
        $relsXpath = new \DOMXPath($relsDoc);
        $relsXpath->registerNamespace('r','http://schemas.openxmlformats.org/package/2006/relationships');
        $existingIds = [];
        foreach ($relsDoc->getElementsByTagName('Relationship') as $relEl) {
            if ($relEl->hasAttribute('Id')) { $existingIds[] = $relEl->getAttribute('Id'); }
        }
        $nextRelId = function() use (&$existingIds) {
            $i = 1; while (in_array('rId'.$i, $existingIds, true)) { $i++; } $existingIds[] = 'rId'.$i; return 'rId'.$i; };

        $signatureMap = [
            'P_REQUESTOR_SIGNATURE' => $requestor['signature'] ?? null,
            'P_APPROVER_SIGNATURE' => $approver['signature'] ?? null,
            'P_SERVICE_STAFF_SIGNATURE' => $serviceStaff['signature'] ?? null,
        ];

        // Normalize signature paths
        foreach ($signatureMap as $k=>$sig) {
            if (!$sig) continue;
            if (strpos($sig, 'uploads/signatures/') === 0) {
                $signatureMap[$k] = FCPATH . $sig;
            } else if (is_file(FCPATH . 'uploads/signatures/' . $sig)) {
                $signatureMap[$k] = FCPATH . 'uploads/signatures/' . $sig;
            } else if (is_file(FCPATH . $sig)) {
                $signatureMap[$k] = FCPATH . $sig;
            } else {
                $signatureMap[$k] = null; // file not found
            }
        }

        // Find all content controls with a matching tag
        $sdtNodes = $xpath->query('//w:sdt[w:sdtPr/w:tag]');
        if (!$sdtNodes) { $zip->close(); return; }

        $modified = false;
        foreach ($sdtNodes as $sdt) {
            $tagNode = $xpath->query('.//w:sdtPr/w:tag', $sdt)->item(0);
            if (!$tagNode || !$tagNode->hasAttribute('w:val')) continue;
            $tag = strtoupper(trim($tagNode->getAttribute('w:val')));
            if (!isset($signatureMap[$tag])) continue; // not a signature tag
            $imgPath = $signatureMap[$tag];
            if (!$imgPath || !is_file($imgPath)) continue;

            // Read image binary
            $imgData = @file_get_contents($imgPath);
            if ($imgData === false) continue;
            // Add image file into media folder with stable name to avoid duplicates
            $ext = strtolower(pathinfo($imgPath, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png','jpg','jpeg'], true)) { $ext = 'png'; }
            $mediaName = 'signature_' . strtolower($tag) . '.' . $ext;
            $zip->addFromString('word/media/' . $mediaName, $imgData);

            // Determine original control drawing size (in EMU) and try to respect it.
            // We'll inspect the existing content control for wp:inline/wp:anchor and any extent values.
            $xpath->registerNamespace('wp','http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
            $xpath->registerNamespace('a','http://schemas.openxmlformats.org/drawingml/2006/main');
            $xpath->registerNamespace('pic','http://schemas.openxmlformats.org/drawingml/2006/picture');

            $controlEmuW = null; $controlEmuH = null;
            $useAnchor = false;
            $existingDocPr = ['id' => null, 'name' => null];
            $existingDist = ['distT' => '0','distB' => '0','distL' => '0','distR' => '0'];

            // Look for inline or anchor drawing inside this sdt (before we clear it)
            $foundInline = $xpath->query('.//wp:inline', $sdt)->item(0);
            $foundAnchor = $xpath->query('.//wp:anchor', $sdt)->item(0);
            $drawingNode = $foundInline ?: $foundAnchor;
            if ($foundAnchor) { $useAnchor = true; }

            if ($drawingNode) {
                // Try to find a wp:extent child (cx/cy in EMU)
                $extentNode = $xpath->query('.//wp:extent', $drawingNode)->item(0);
                if ($extentNode && $extentNode->hasAttribute('cx') && $extentNode->hasAttribute('cy')) {
                    $controlEmuW = (int)$extentNode->getAttribute('cx');
                    $controlEmuH = (int)$extentNode->getAttribute('cy');
                } else {
                    // Fallback: check nested a:ext (sometimes drawing stores extents here)
                    $aExt = $xpath->query('.//a:ext', $drawingNode)->item(0);
                    if ($aExt && $aExt->hasAttribute('cx') && $aExt->hasAttribute('cy')) {
                        $controlEmuW = (int)$aExt->getAttribute('cx');
                        $controlEmuH = (int)$aExt->getAttribute('cy');
                    }
                }

                // Capture existing docPr id/name if present so we can reuse them
                $docPrNode = $xpath->query('.//wp:docPr', $drawingNode)->item(0);
                if ($docPrNode) {
                    if ($docPrNode->hasAttribute('id')) { $existingDocPr['id'] = $docPrNode->getAttribute('id'); }
                    if ($docPrNode->hasAttribute('name')) { $existingDocPr['name'] = $docPrNode->getAttribute('name'); }
                }
                // Capture any dist* attributes present on inline/anchor
                foreach (['distT','distB','distL','distR'] as $dAttr) {
                    if ($drawingNode->hasAttribute($dAttr)) { $existingDist[$dAttr] = $drawingNode->getAttribute($dAttr); }
                }
            }

            // Determine image original size in px and compute target EMU using control extents when available
            $emuX = 0; $emuY = 0;
            if ($info = @getimagesize($imgPath)) {
                $origW = max(1, (int)$info[0]);
                $origH = max(1, (int)$info[1]);
                $imageEmuW = (int)round($origW * 9525);
                $imageEmuH = (int)round($origH * 9525);

                if ($controlEmuW && $controlEmuH) {
                    // Fit image into control extents preserving aspect ratio
                    $scale = min($controlEmuW / $imageEmuW, $controlEmuH / $imageEmuH);
                    // Avoid division by zero; ensure scale is sensible
                    if ($scale <= 0) { $scale = 1.0; }
                    $emuX = (int)round($imageEmuW * $scale);
                    $emuY = (int)round($imageEmuH * $scale);
                } else {
                    // No control dims available; fallback to small target width (px) like before
                    $targetW = 200; // px
                    $scale = $targetW / $origW;
                    $pxW = (int)round($origW * $scale);
                    $pxH = (int)round($origH * $scale);
                    $emuX = (int)round($pxW * 9525);
                    $emuY = (int)round($pxH * 9525);
                }
            } else {
                // No image info; use tiny default in EMU
                $emuX = (int)(120 * 9525);
                $emuY = (int)(40 * 9525);
            }

            // Create relationship
            $relsRoot = $relsDoc->documentElement;
            $relId = $nextRelId();
            $relEl = $relsDoc->createElement('Relationship');
            $relEl->setAttribute('Id', $relId);
            $relEl->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image');
            $relEl->setAttribute('Target', 'media/' . $mediaName);
            $relsRoot->appendChild($relEl);

            // Build drawing XML
            $wNs = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
            $wpNs = 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing';
            $aNs = 'http://schemas.openxmlformats.org/drawingml/2006/main';
            $picNs = 'http://schemas.openxmlformats.org/drawingml/2006/picture';
            $rNs = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

            // Prefer to replace an existing w:drawing inside the content control so the
            // original paragraph/run location is preserved. If no drawing exists, fall
            // back to appending a new paragraph containing the image.
            $contentNode = $xpath->query('.//w:sdtContent', $sdt)->item(0);
            if (!$contentNode) continue;

            $existingDrawingNode = $xpath->query('.//w:drawing', $contentNode)->item(0);

            // Build the new drawing node (same as before)
            $newDrawing = $doc->createElementNS($wNs, 'w:drawing');
            $inline = $doc->createElementNS($wpNs, $useAnchor ? 'wp:anchor' : 'wp:inline');
            foreach ($existingDist as $k=>$v) { $inline->setAttribute($k, (string)$v); }
            $extent = $doc->createElementNS($wpNs, 'wp:extent');
            $extent->setAttribute('cx', (string)$emuX); $extent->setAttribute('cy', (string)$emuY);
            $effect = $doc->createElementNS($wpNs, 'wp:effectExtent');
            $effect->setAttribute('l','0');$effect->setAttribute('t','0');$effect->setAttribute('r','0');$effect->setAttribute('b','0');
            $docPr = $doc->createElementNS($wpNs, 'wp:docPr');
            $docPr->setAttribute('id', $existingDocPr['id'] ?? '1'); $docPr->setAttribute('name', $existingDocPr['name'] ?? $tag);
            $cNv = $doc->createElementNS($wpNs, 'wp:cNvGraphicFramePr');
            $graphic = $doc->createElementNS($aNs, 'a:graphic');
            $graphicData = $doc->createElementNS($aNs, 'a:graphicData');
            $graphicData->setAttribute('uri','http://schemas.openxmlformats.org/drawingml/2006/picture');
            $pic = $doc->createElementNS($picNs, 'pic:pic');
            $nvPicPr = $doc->createElementNS($picNs, 'pic:nvPicPr');
            $cNvPr = $doc->createElementNS($picNs, 'pic:cNvPr');
            $cNvPr->setAttribute('id','0'); $cNvPr->setAttribute('name', $mediaName);
            $cNvPicPr = $doc->createElementNS($picNs, 'pic:cNvPicPr');
            $nvPicPr->appendChild($cNvPr); $nvPicPr->appendChild($cNvPicPr);
            $blipFill = $doc->createElementNS($picNs, 'pic:blipFill');
            $blip = $doc->createElementNS($aNs, 'a:blip');
            $blip->setAttributeNS($rNs, 'r:embed', $relId);
            $stretch = $doc->createElementNS($aNs, 'a:stretch');
            $fillRect = $doc->createElementNS($aNs, 'a:fillRect');
            $stretch->appendChild($fillRect);
            $blipFill->appendChild($blip); $blipFill->appendChild($stretch);
            $spPr = $doc->createElementNS($picNs, 'pic:spPr');
            $xfrm = $doc->createElementNS($aNs, 'a:xfrm');
            $off = $doc->createElementNS($aNs, 'a:off'); $off->setAttribute('x','0'); $off->setAttribute('y','0');
            $ext = $doc->createElementNS($aNs, 'a:ext'); $ext->setAttribute('cx',(string)$emuX); $ext->setAttribute('cy',(string)$emuY);
            $xfrm->appendChild($off); $xfrm->appendChild($ext);
            $prst = $doc->createElementNS($aNs, 'a:prstGeom'); $prst->setAttribute('prst','rect');
            $avLst = $doc->createElementNS($aNs, 'a:avLst'); $prst->appendChild($avLst);
            $spPr->appendChild($xfrm); $spPr->appendChild($prst);

            $pic->appendChild($nvPicPr); $pic->appendChild($blipFill); $pic->appendChild($spPr);
            $graphicData->appendChild($pic); $graphic->appendChild($graphicData);
            $inline->appendChild($extent); $inline->appendChild($effect); $inline->appendChild($docPr); $inline->appendChild($cNv); $inline->appendChild($graphic);
            $newDrawing->appendChild($inline);

            if ($existingDrawingNode && $existingDrawingNode->parentNode) {
                // Replace the existing drawing node so paragraph/run and overall position are preserved
                $existingDrawingNode->parentNode->replaceChild($newDrawing, $existingDrawingNode);
            } else {
                // No drawing present: remove existing children and append a new paragraph with the image
                while ($contentNode->firstChild) { $contentNode->removeChild($contentNode->firstChild); }
                $p = $doc->createElementNS($wNs, 'w:p');
                $r = $doc->createElementNS($wNs, 'w:r');
                $r->appendChild($newDrawing);
                $p->appendChild($r);
                $contentNode->appendChild($p);
            }
            $modified = true;
        }

        if ($modified) {
            $zip->addFromString('word/document.xml', $doc->saveXML());
            $zip->addFromString('word/_rels/document.xml.rels', $relsDoc->saveXML());
        }
        $zip->close();
    }
    
    /**
     * Clean up old temporary files to prevent disk space issues
     * 
     * @param string $directory Directory to clean
     * @param int $maxAge Maximum age in seconds (default 1 hour)
     * @return void
     */
    private function cleanupOldTempFiles(string $directory, int $maxAge = 3600): void
    {
        try {
            if (!is_dir($directory)) {
                return;
            }
            
            $now = time();
            $files = glob($directory . 'form_*');
            
            if ($files === false) {
                return;
            }
            
            $cleaned = 0;
            foreach ($files as $file) {
                if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
                    if (@unlink($file)) {
                        $cleaned++;
                    }
                }
            }
            
            if ($cleaned > 0) {
                log_message('info', "Cleaned up $cleaned old temporary files");
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Failed to clean up temp files: ' . $e->getMessage());
        }
    }
}
