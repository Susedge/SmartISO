<?php

namespace App\Controllers;

use App\Models\FormModel;
use App\Models\FormSubmissionModel;

class FormDownload extends BaseController
{
    protected $formModel;
    protected $formSubmissionModel;

    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->formSubmissionModel = new FormSubmissionModel();
    }

    /**
     * Download a blank/fillable DOCX template for a form (if present)
     */
    public function downloadWord($formCode)
    {
        if (!$formCode) {
            return redirect()->to('/')->with('error', 'Invalid form code');
        }

        $form = $this->formModel->where('code', $formCode)->first();
        if (!$form) {
            return redirect()->to('/')->with('error', 'Form not found');
        }

        $templatePath = FCPATH . 'templates/docx/' . $form['code'] . '_template.docx';
        if (!file_exists($templatePath)) {
            return redirect()->back()->with('error', 'Template file not found');
        }

        return $this->response->download($templatePath, null);
    }

    /**
     * Download a pre-generated PDF for the form template (if present)
     */
    public function downloadPDF($formCode)
    {
        if (!$formCode) {
            return redirect()->to('/')->with('error', 'Invalid form code');
        }

        $form = $this->formModel->where('code', $formCode)->first();
        if (!$form) {
            return redirect()->to('/')->with('error', 'Form not found');
        }

        $pdfPath = FCPATH . 'templates/pdf/' . $form['code'] . '.pdf';
        if (!file_exists($pdfPath)) {
            return redirect()->back()->with('error', 'PDF template not found');
        }

        return $this->response->download($pdfPath, null);
    }

    /**
     * Download the originally uploaded template (PDF or DOCX). Prefers PDF if available.
     */
    public function downloadUploaded($formCode)
    {
        if (!$formCode) {
            return redirect()->to('/')->with('error', 'Invalid form code');
        }

        $form = $this->formModel->where('code', $formCode)->first();
        if (!$form) {
            return redirect()->to('/')->with('error', 'Form not found');
        }

        $pdfPath = FCPATH . 'templates/pdf/' . $form['code'] . '.pdf';
        $docxPath = FCPATH . 'templates/docx/' . $form['code'] . '_template.docx';

        if (file_exists($pdfPath)) {
            return $this->response->download($pdfPath, null);
        }

        if (file_exists($docxPath)) {
            return $this->response->download($docxPath, null);
        }

        return redirect()->back()->with('error', 'Template file not found');
    }
}
