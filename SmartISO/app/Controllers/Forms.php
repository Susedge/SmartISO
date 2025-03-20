<?php

namespace App\Controllers;

use App\Models\FormModel;
use App\Models\DbpanelModel;
use App\Models\FormSubmissionModel;
use App\Models\FormSubmissionDataModel;
use App\Models\DepartmentModel;

class Forms extends BaseController
{
    protected $formModel;
    protected $dbpanelModel;
    protected $formSubmissionModel;
    protected $formSubmissionDataModel;
    protected $departmentModel;
    
    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->dbpanelModel = new DbpanelModel();
        $this->formSubmissionModel = new FormSubmissionModel();
        $this->formSubmissionDataModel = new FormSubmissionDataModel();
        $this->departmentModel = new DepartmentModel();
    }
    
    public function index()
    {
        $data = [
            'title' => 'Available Forms',
            'forms' => $this->formModel->findAll()
        ];
        
        return view('forms/index', $data);
    }
    
    public function view($formCode)
    {
        $form = $this->formModel->where('code', $formCode)->first();
        
        if (!$form) {
            return redirect()->to('/forms')->with('error', 'Form not found');
        }
        
        // Get panel fields
        $panelFields = $this->dbpanelModel->getPanelFields($formCode);
        
        if (empty($panelFields)) {
            return redirect()->to('/forms')->with('error', 'No fields configured for this form');
        }
        
        $data = [
            'title' => 'Form: ' . $form['description'],
            'form' => $form,
            'panel_name' => $formCode,
            'panel_fields' => $panelFields,
            'departments' => $this->departmentModel->findAll()
        ];
        
        return view('forms/view', $data);
    }
    
    public function submit()
    {
        $formId = $this->request->getPost('form_id');
        $panelName = $this->request->getPost('panel_name');

        log_message('debug', 'POST data: ' . json_encode($this->request->getPost()));
        
        if (!$formId || !$panelName) {
            return redirect()->back()->with('error', 'Form ID and Panel Name are required');
        }
        
        // Get all panel fields to validate
        $panelFields = $this->dbpanelModel->getPanelFields($panelName);
        
        if (empty($panelFields)) {
            return redirect()->back()->with('error', 'No fields configured for this panel');
        }
        
        // Create a new submission record
        $submissionId = $this->formSubmissionModel->insert([
            'form_id' => $formId,
            'panel_name' => $panelName,
            'submitted_by' => session()->get('user_id'),
            'status' => 'submitted'
        ]);
        
        // Save each field value
        foreach ($panelFields as $field) {
            $fieldName = $field['field_name'];
            $fieldValue = $this->request->getPost($fieldName) ?? '';
            
            // Debug each field
            log_message('debug', "Field {$fieldName}: " . ($this->request->getPost($fieldName) ? 'Has value' : 'No value'));
            
            $this->formSubmissionDataModel->insert([
                'submission_id' => $submissionId,
                'field_name' => $fieldName,
                'field_value' => $fieldValue
            ]);
        }
        
        return redirect()->to('/forms/my-submissions')
                        ->with('message', 'Form submitted successfully');
    }
    
    public function mySubmissions()
    {
        $userId = session()->get('user_id');
        
        $data = [
            'title' => 'My Form Submissions',
            'submissions' => $this->formSubmissionModel->getSubmissionsWithDetails($userId)
        ];
        
        return view('forms/my_submissions', $data);
    }
    
    public function viewSubmission($id)
    {
        $userId = session()->get('user_id');
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission || $submission['submitted_by'] != $userId) {
            return redirect()->to('/forms/my-submissions')
                            ->with('error', 'Submission not found or you don\'t have permission to view it');
        }
        
        // Get form details
        $form = $this->formModel->find($submission['form_id']);
        
        // Get panel fields
        $panelFields = $this->dbpanelModel->getPanelFields($submission['panel_name']);
        
        // Get submission data
        $submissionData = $this->formSubmissionDataModel->getSubmissionDataAsArray($id);
        
        $data = [
            'title' => 'View Submission',
            'submission' => $submission,
            'form' => $form,
            'panel_fields' => $panelFields,
            'submission_data' => $submissionData
        ];
        
        return view('forms/view_submission', $data);
    }

    public function export($id, $format = 'pdf')
    {
        $userId = session()->get('user_id');
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission || $submission['submitted_by'] != $userId) {
            return redirect()->to('/forms/my-submissions')
                            ->with('error', 'Submission not found or you don\'t have permission to export it');
        }
        
        // Only allow exporting approved submissions
        if ($submission['status'] != 'approved') {
            return redirect()->to('/forms/submission/' . $id)
                            ->with('error', 'Only approved submissions can be exported');
        }
        
        // Get form details
        $form = $this->formModel->find($submission['form_id']);
        
        // Get panel fields
        $panelFields = $this->dbpanelModel->getPanelFields($submission['panel_name']);
        
        // Get submission data
        $submissionData = $this->formSubmissionDataModel->getSubmissionDataAsArray($id);
        
        if ($format == 'pdf') {
            // In a real app, you'd generate a PDF using a library like TCPDF or DOMPDF
            // For this example, we'll just return a message
            return redirect()->back()->with('message', 'PDF export functionality would be implemented here');
        } else if ($format == 'excel') {
            // In a real app, you'd generate an Excel file using a library like PhpSpreadsheet
            // For this example, we'll just return a message
            return redirect()->back()->with('message', 'Excel export functionality would be implemented here');
        }
        
        return redirect()->back()->with('error', 'Invalid export format');
    }
}
