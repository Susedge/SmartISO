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
    
    public function generateFormPdf($submissionId)
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        $submission = $this->formSubmissionModel->find($submissionId);
        
        if (!$submission) {
            return redirect()->to('/dashboard')
                            ->with('error', 'Submission not found');
        }
        
        // Check permissions - allow export for completed forms
        $canExport = false;
        
        if ($userType === 'admin' || $userType === 'approving_authority' || $userType === 'service_staff') {
            $canExport = ($submission['status'] == 'completed');
        } else if ($userType === 'requestor' && $submission['submitted_by'] == $userId) {
            $canExport = ($submission['status'] == 'completed');
        }
        
        if (!$canExport) {
            return redirect()->back()
                            ->with('error', 'You don\'t have permission to export this submission or it is not completed');
        }
        
        // Get form details
        $form = $this->formModel->find($submission['form_id']);
        
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
            $submissionData
        );
        
        // Determine which template to use
        $templatePath = FCPATH . 'templates/docx/' . $form['code'] . '_template.docx';
        if (!file_exists($templatePath)) {
            // Fall back to default template
            $templatePath = FCPATH . 'templates/docx/default_form_template.docx';
            
            // If no default exists either, return error
            if (!file_exists($templatePath)) {
                return redirect()->back()->with('error', 'DOCX template not found');
            }
        }
        
        try {
            // Load the Word template
            $templateProcessor = new TemplateProcessor($templatePath);
            
            // Replace all text placeholders
            foreach ($placeholderValues as $placeholder => $value) {
                // Remove {{ }} for PHPWord format
                $placeholderName = trim($placeholder, '{}');
                $templateProcessor->setValue($placeholderName, $value ?? '');
            }
            
            // Add signature images
            $this->addSignatureImages($templateProcessor, $requestor, $approver, $serviceStaff);
            
            // Create a temporary directory if it doesn't exist
            $tempDir = WRITEPATH . 'temp/';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Save the processed document to a temp file
            $tempDocxFile = $tempDir . uniqid('form_') . '.docx';
            $templateProcessor->saveAs($tempDocxFile);
            
            // Generate a unique filename for the Word document
            $docxFilename = 'Form_' . $form['code'] . '_Submission_' . $submissionId . '.docx';
            
            // Just return the DOCX directly since PDF conversion is problematic
            return $this->response->download($tempDocxFile, null)
                                ->setFileName($docxFilename);
            
        } catch (\Exception $e) {
            log_message('error', 'Document generation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error generating document: ' . $e->getMessage());
        }
    }
    
    /**
     * Prepare all text placeholder values from the form data
     */
    private function prepareTextPlaceholders($form, $submission, $submissionId, $requestor, $requestorDept, $approver, $serviceStaff, $panelFields, $submissionData)
    {
        $placeholders = [
            // Basic form information
            '{{FORM_ID}}' => $form['id'],
            '{{FORM_CODE}}' => $form['code'],
            '{{FORM_DESCRIPTION}}' => $form['description'],
            '{{FORM_STATUS}}' => ucfirst($submission['status']),
            
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
            $placeholders['{{APPROVAL_COMMENTS}}'] = $submission['approval_comments'] ?? '';
        } else {
            $placeholders['{{APPROVER_ID}}'] = '';
            $placeholders['{{APPROVER_NAME}}'] = '';
            $placeholders['{{APPROVER_EMAIL}}'] = '';
            $placeholders['{{APPROVAL_DATE}}'] = '';
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
        
        // Add form fields to placeholders
        foreach ($panelFields as $field) {
            $fieldName = $field['field_name'];
            $fieldValue = isset($submissionData[$fieldName]) ? $submissionData[$fieldName] : '';
            $placeholders['{{' . strtoupper($fieldName) . '}}'] = $fieldValue;
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
                    // Try using the simpler version without the options array
                    $templateProcessor->setImageValue('REQUESTOR_SIGNATURE', $signaturePath);
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
                    // Try using the simpler version without the options array
                    $templateProcessor->setImageValue('APPROVER_SIGNATURE', $signaturePath);
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
                    // Try using the simpler version without the options array
                    $templateProcessor->setImageValue('SERVICE_STAFF_SIGNATURE', $signaturePath);
                } catch (\Exception $e) {
                    log_message('error', 'Failed to add service staff signature: ' . $e->getMessage());
                }
            } else {
                log_message('warning', 'Service staff signature file not found: ' . $signaturePath);
            }
        }
    }
}
