<?php

namespace App\Controllers;

use App\Models\FormModel;
use App\Models\DbpanelModel;
use App\Models\FormSubmissionModel;
use App\Models\FormSubmissionDataModel;
use PhpOffice\PhpWord\TemplateProcessor;

class FormDownload extends BaseController
{
    protected $formModel;
    protected $dbpanelModel;
    protected $formSubmissionModel;
    protected $formSubmissionDataModel;
    
    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->dbpanelModel = new DbpanelModel();
        $this->formSubmissionModel = new FormSubmissionModel();
        $this->formSubmissionDataModel = new FormSubmissionDataModel();
    }
    
    /**
     * Generate downloadable PDF form without placeholders
     */
    public function downloadPDF($formCode)
    {
        $form = $this->formModel->where('code', $formCode)->first();
        
        if (!$form) {
            return redirect()->to('/forms')->with('error', 'Form not found');
        }
        
        // Get panel fields
        $panelName = !empty($form['panel_name']) ? $form['panel_name'] : $formCode;
        $panelFields = $this->dbpanelModel->getPanelFields($panelName);
        
        if (empty($panelFields)) {
            return redirect()->to('/forms')->with('error', 'No fields configured for this form');
        }
        
        // Generate fillable PDF
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('SmartISO System');
        $pdf->SetAuthor('SmartISO');
        $pdf->SetTitle($form['description']);
        $pdf->SetSubject('Fillable Form Template');
        
        // Set margins
        $pdf->SetMargins(20, 30, 20);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        
        // Add a page
        $pdf->AddPage();
        
        // Add header with current date
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 15, $form['description'], 0, 1, 'C');
        
        // Add date filled (system date)
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 8, 'Date: ' . date('F j, Y'), 0, 1, 'R');
        $pdf->Ln(10);
        
        // Office field (not predefined)
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(60, 8, 'Office/Department:', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(120, 8, '', 1, 1, 'L');
        $pdf->Ln(5);
        
        // Add form fields
        $yPosition = $pdf->GetY() + 10;
        
        foreach ($panelFields as $field) {
            // Skip service staff only fields for template download
            if (isset($field['field_role']) && $field['field_role'] === 'service_staff') {
                continue;
            }
            
            $pdf->SetY($yPosition);
            
            // Field label
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(60, 8, $field['field_label'] . ':', 0, 0, 'L');
            
            // Field input area based on type - clean template without placeholders
            $pdf->SetFont('helvetica', '', 10);
            
            switch ($field['field_type']) {
                case 'textarea':
                    // Multi-line text area
                    $pdf->Rect(65, $yPosition, 120, 25);
                    $yPosition += 30;
                    break;
                    
                case 'dropdown':
                    // Empty checkbox options for clean template
                    $pdf->Cell(120, 8, '☐ __________ ☐ __________ ☐ __________ ☐ Other: ___________', 1, 1, 'L');
                    $yPosition += 12;
                    break;
                    
                case 'datepicker':
                    // Clean date field
                    $pdf->Cell(30, 8, '', 1, 0, 'C');
                    $pdf->Cell(90, 8, ' (MM/DD/YYYY)', 0, 1, 'L');
                    $yPosition += 12;
                    break;
                    
                
                    
                default: // input
                    // Single line text
                    $pdf->Cell(120, 8, '', 1, 1, 'L');
                    $yPosition += 12;
                    break;
            }
            
            $yPosition += 5; // Spacing between fields
            
            // Add new page if needed
            if ($yPosition > 250) {
                $pdf->AddPage();
                $yPosition = 30;
            }
        }
        
        // Add signature section
        $pdf->SetY(max($yPosition + 20, 220));
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Signatures:', 0, 1, 'L');
        $pdf->Ln(5);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(80, 8, 'Requestor Signature:', 0, 0, 'L');
        $pdf->Cell(80, 8, 'Date:', 0, 1, 'L');
        $pdf->Cell(80, 15, '', 1, 0, 'L');
        $pdf->Cell(80, 15, '', 1, 1, 'L');
        $pdf->Ln(10);
        
        $pdf->Cell(80, 8, 'Approving Authority Signature:', 0, 0, 'L');
        $pdf->Cell(80, 8, 'Date:', 0, 1, 'L');
        $pdf->Cell(80, 15, '', 1, 0, 'L');
        $pdf->Cell(80, 15, '', 1, 1, 'L');
        
        // Add footer with form info
        $pdf->SetY(280);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 5, 'Form Template: ' . $formCode . ' | Downloaded: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        
        // Output PDF
        $filename = $form['code'] . '_template.pdf';
        $pdf->Output($filename, 'D');
    }
    
    /**
     * Generate downloadable Word document form
     */
    public function downloadWord($formCode)
    {
        $form = $this->formModel->where('code', $formCode)->first();
        
        if (!$form) {
            return redirect()->to('/forms')->with('error', 'Form not found');
        }
        
        // Get panel fields
        $panelName = !empty($form['panel_name']) ? $form['panel_name'] : $formCode;
        $panelFields = $this->dbpanelModel->getPanelFields($panelName);
        
        // Create new Word document
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        
        // Add document properties
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('SmartISO System');
        $properties->setCompany('SmartISO');
        $properties->setTitle($form['description']);
        $properties->setDescription('Fillable form generated by SmartISO');
        
        // Add a section
        $section = $phpWord->addSection([
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1000,
            'marginRight' => 1000
        ]);
        
        // Add title
        $section->addText($form['description'], [
            'name' => 'Arial',
            'size' => 16,
            'bold' => true
        ], ['alignment' => 'center']);
        
        $section->addTextBreak(2);
        
        // Add form fields
        foreach ($panelFields as $field) {
            // Skip service staff only fields
            if (isset($field['field_role']) && $field['field_role'] === 'service_staff') {
                continue;
            }
            
            // Add field label
            $section->addText($field['field_label'] . ':', [
                'name' => 'Arial',
                'size' => 11,
                'bold' => true
            ]);
            
            // Add field input based on type
            switch ($field['field_type']) {
                case 'textarea':
                    // Multi-line input
                    $section->addText(str_repeat('_', 80), ['name' => 'Arial', 'size' => 10]);
                    $section->addTextBreak();
                    $section->addText(str_repeat('_', 80), ['name' => 'Arial', 'size' => 10]);
                    $section->addTextBreak();
                    $section->addText(str_repeat('_', 80), ['name' => 'Arial', 'size' => 10]);
                    break;
                    
                case 'dropdown':
                    $section->addText('☐ Option 1    ☐ Option 2    ☐ Option 3    ☐ Other: _____________', [
                        'name' => 'Arial', 'size' => 10
                    ]);
                    break;
                    
                case 'datepicker':
                    $section->addText('Date: ___/___/_____ (MM/DD/YYYY)', [
                        'name' => 'Arial', 'size' => 10
                    ]);
                    break;
                    
                
                    
                default: // input
                    $section->addText(str_repeat('_', 50), [
                        'name' => 'Arial', 'size' => 10
                    ]);
                    break;
            }
            
            $section->addTextBreak(2);
        }
        
        // Add footer with form info
        $section->addTextBreak(3);
        $section->addText('Form Code: ' . $formCode . ' | Generated: ' . date('Y-m-d H:i:s'), [
            'name' => 'Arial', 'size' => 8, 'color' => '666666'
        ], ['alignment' => 'center']);
        
        // Save and download
        $filename = $form['code'] . '_fillable_form.docx';
        $tempFile = WRITEPATH . 'temp/' . $filename;
        
        // Ensure temp directory exists
        if (!is_dir(WRITEPATH . 'temp/')) {
            mkdir(WRITEPATH . 'temp/', 0755, true);
        }
        
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);
        
        // Force download
        return $this->response->download($tempFile, null)->setFileName($filename);
    }

    /**
     * Download the uploaded template file (prefer PDF if uploaded, otherwise DOCX)
     */
    public function downloadUploaded($formCode)
    {
        $form = $this->formModel->where('code', $formCode)->first();
        if (!$form) {
            return redirect()->to('/forms')->with('error', 'Form not found');
        }

        // Check for uploaded PDF first
        $pdfPath = FCPATH . 'templates/pdf/' . $form['code'] . '.pdf';
        if (file_exists($pdfPath)) {
            return $this->response->download($pdfPath, null)->setFileName($form['code'] . '_template.pdf');
        }

        // Fall back to DOCX uploaded template
        $docxPath = FCPATH . 'templates/docx/' . $form['code'] . '_template.docx';
        if (file_exists($docxPath)) {
            // If the current user is a requestor, replace any template placeholders with empty strings
            // so the downloaded template does not contain unreplaced variables.
            try {
                $tempDir = WRITEPATH . 'temp/';
                if (!is_dir($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }

                $servePath = $docxPath;

                if (session()->get('user_type') === 'requestor') {
                    try {
                        $template = new TemplateProcessor($docxPath);
                        $vars = [];
                        try {
                            $vars = $template->getVariables();
                        } catch (\Exception $e) {
                            // If TemplateProcessor cannot list variables, ignore and continue
                            $vars = [];
                        }

                        foreach ($vars as $v) {
                            // set empty value for every placeholder
                            try {
                                $template->setValue($v, '');
                            } catch (\Exception $e) {
                                // ignore failures for individual vars
                            }
                        }

                        $tempDocx = $tempDir . uniqid($form['code'] . '_') . '.docx';
                        $template->saveAs($tempDocx);

                        // schedule cleanup
                        register_shutdown_function(function($path) {
                            try {
                                if (file_exists($path)) {
                                    @unlink($path);
                                }
                            } catch (\Exception $e) {
                                log_message('warning', 'Failed to delete temp DOCX: ' . $e->getMessage());
                            }
                        }, $tempDocx);

                        $servePath = $tempDocx;
                    } catch (\Exception $e) {
                        // If placeholder processing fails, fall back to original docx
                        log_message('warning', 'Failed to strip placeholders from template: ' . $e->getMessage());
                        $servePath = $docxPath;
                    }
                }

                // Prefer iLovePDF conversion when SDK and credentials are available (better fidelity)
                $ilovepdfPublic = env('ILOVEPDF_PUBLIC_KEY') ?: getenv('ILOVEPDF_PUBLIC_KEY');
                $ilovepdfSecret = env('ILOVEPDF_SECRET_KEY') ?: getenv('ILOVEPDF_SECRET_KEY');

        if (!empty($ilovepdfPublic) && !empty($ilovepdfSecret) && class_exists('\\Ilovepdf\\Ilovepdf')) {
                    try {
            $ilovepdf = new \Ilovepdf\Ilovepdf($ilovepdfPublic, $ilovepdfSecret);
                        $task = $ilovepdf->newTask('officepdf');
                        $task->addFile($servePath);
                        $task->execute();

                        // download to temp dir
                        $task->download($tempDir);

                        // Find newest PDF in temp dir produced by ilovepdf
                        $pdfMatches = glob($tempDir . '*.pdf');
                        usort($pdfMatches, function($a, $b) { return filemtime($b) <=> filemtime($a); });
                        if (!empty($pdfMatches) && file_exists($pdfMatches[0])) {
                            $tempPdf = $pdfMatches[0];
                            register_shutdown_function(function($path) {
                                try { if (file_exists($path)) { @unlink($path); } } catch (\Exception $e) {}
                            }, $tempPdf);

                            return $this->response->download($tempPdf, null)->setFileName($form['code'] . '_template.pdf');
                        }
                    } catch (\Exception $e) {
                        log_message('warning', 'iLovePDF conversion failed or returned no PDF: ' . $e->getMessage());
                        // fall through to next conversion/mime serving strategy
                    }
                }

                // Attempt to convert to PDF if environment supports it, otherwise serve DOCX
                if (class_exists('\\PhpOffice\\PhpWord\\IOFactory') && class_exists('\\Dompdf\\Dompdf')) {
                    try {
                        $phpWord = \PhpOffice\PhpWord\IOFactory::load($servePath);
                        // register DomPDF renderer if possible
                        try {
                            \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
                        } catch (\Exception $e) {
                            // ignore
                        }

                        $tempPdf = $tempDir . uniqid($form['code'] . '_') . '.pdf';
                        $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
                        $pdfWriter->save($tempPdf);

                        register_shutdown_function(function($path) {
                            try { if (file_exists($path)) { @unlink($path); } } catch (\Exception $e) {}
                        }, $tempPdf);

                        return $this->response->download($tempPdf, null)->setFileName($form['code'] . '_template.pdf');
                    } catch (\Exception $e) {
                        log_message('warning', 'DOCX->PDF conversion failed: ' . $e->getMessage());
                        return $this->response->download($servePath, null)->setFileName($form['code'] . '_template.docx');
                    }
                }

                return $this->response->download($servePath, null)->setFileName($form['code'] . '_template.docx');
            } catch (\Exception $e) {
                log_message('error', 'Error serving uploaded template: ' . $e->getMessage());
                return redirect()->back()->with('error', 'No uploaded template found for this form');
            }
        }

        return redirect()->back()->with('error', 'No uploaded template found for this form');
    }
    
    /**
     * Show import form for completed documents
     */
    public function importForm()
    {
        $data = [
            'title' => 'Import Completed Form',
            'forms' => $this->formModel->findAll()
        ];
        
        return view('forms/import', $data);
    }
    
    /**
     * Process imported form data
     */
    public function processImport()
    {
        $formCode = $this->request->getPost('form_code');
        $importType = $this->request->getPost('import_type'); // 'manual' or 'upload'
        
        if ($importType === 'manual') {
            return $this->processManualImport($formCode);
        } else {
            return $this->processFileImport($formCode);
        }
    }
    
    /**
     * Process manual data entry import
     */
    private function processManualImport($formCode)
    {
        $form = $this->formModel->where('code', $formCode)->first();
        if (!$form) {
            return redirect()->back()->with('error', 'Form not found');
        }
        
        // Get panel fields
        $panelName = !empty($form['panel_name']) ? $form['panel_name'] : $formCode;
        $panelFields = $this->dbpanelModel->getPanelFields($panelName);
        
        // Create submission
        $submissionId = $this->formSubmissionModel->insert([
            'form_id' => $form['id'],
            'panel_name' => $panelName,
            'submitted_by' => session()->get('user_id'),
            'status' => 'submitted',
            'import_method' => 'manual_entry'
        ]);
        
        // Save field data
        foreach ($panelFields as $field) {
            $fieldValue = $this->request->getPost($field['field_name']) ?? '';
            
            if (!empty($fieldValue)) {
                $this->formSubmissionDataModel->insert([
                    'submission_id' => $submissionId,
                    'field_name' => $field['field_name'],
                    'field_value' => $fieldValue
                ]);
            }
        }
        
        return redirect()->to('/forms/my-submissions')
                        ->with('message', 'Form imported successfully via manual entry');
    }
    
    /**
     * Process file upload import (future OCR integration)
     */
    private function processFileImport($formCode)
    {
        $uploadedFile = $this->request->getFile('import_file');
        
        if (!$uploadedFile->isValid()) {
            return redirect()->back()->with('error', 'Invalid file upload');
        }
        
        // For now, just save the file and redirect to manual entry
        // TODO: Implement OCR or PDF parsing here
        
        $newName = $uploadedFile->getRandomName();
        $uploadedFile->move(WRITEPATH . 'uploads/imports/', $newName);
        
        return redirect()->back()->with('message', 
            'File uploaded successfully. OCR processing will be implemented in future version. Please use manual entry for now.');
    }
}
