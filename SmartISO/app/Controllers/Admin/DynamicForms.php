<?php

namespace App\Controllers\Admin;

use CodeIgniter\Model;
use App\Controllers\BaseController;
use App\Models\DbpanelModel;
use App\Models\FormModel;
use App\Models\OfficeModel;
use App\Models\DepartmentModel;
use App\Models\FormSubmissionModel;
use App\Models\FormSubmissionDataModel;

class DynamicForms extends BaseController
{
    /**
     * Rename a panel (update all rows with old_panel_name to new_panel_name)
     * Expects POST: old_panel_name, new_panel_name
     * Returns JSON: { success: true/false, message: string }
     */
    public function renamePanel()
    {
        $oldName = $this->request->getPost('old_panel_name');
        $newName = $this->request->getPost('new_panel_name');
        if (!$oldName || !$newName) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Both old and new panel names are required.'
            ]);
        }
        // Check if new name already exists
        $exists = $this->dbpanelModel->where('panel_name', $newName)->countAllResults();
        if ($exists) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Panel name already exists.'
            ]);
        }
        $result = $this->dbpanelModel->renamePanel($oldName, $newName);
        if ($result) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Panel renamed successfully.'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to rename panel.'
            ]);
        }
    }
    protected $dbpanelModel;
    protected $formModel;
    protected $departmentModel;
    protected $officeModel;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->dbpanelModel = new DbpanelModel();
        $this->formModel = new FormModel();
        $this->departmentModel = new DepartmentModel();
    $this->officeModel = new OfficeModel();
        $this->formSubmissionModel = new FormSubmissionModel();
        $this->formSubmissionDataModel = new FormSubmissionDataModel();
    }
    
    public function index()
    {
        $data = [
            'title' => 'Forms',
            'forms' => $this->formModel->findAll(),
            'panels' => $this->dbpanelModel->getPanels(), // Get unique panel names
            'offices' => $this->officeModel->getActiveOffices()
        ];
        
        return view('admin/dynamicforms/index', $data);
    }
    
    public function panel()
    {
        $formId = $this->request->getGet('form_id');
        $panelName = $this->request->getGet('panel_name');
        
        if (!$formId || !$panelName) {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Form ID and Panel Name are required');
        }
        
        $form = $this->formModel->find($formId);
        if (!$form) {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Form not found');
        }
        
        $panelFields = $this->dbpanelModel->getPanelFields($panelName);
        
        $data = [
            'title' => 'Form: ' . $form['description'],
            'form' => $form,
            'panel_name' => $panelName,
            'panel_fields' => $panelFields,
            'departments' => $this->departmentModel->findAll()
        ];
        
        return view('admin/dynamicforms/form', $data);
    }
    
    public function panelConfig()
    {
        $data = [
            'title' => 'Panels',
            'panels' => $this->dbpanelModel->getPanels()
        ];
        
        return view('admin/dynamicforms/panel_config', $data);
    }

    /**
     * Show placeholders & DOCX variable guide for admins
     */
    public function guide()
    {
        $data = [
            'title' => 'DOCX Variables Guide'
        ];

        return view('admin/dynamicforms/guide', $data);
    }
    
    public function createPanel()
    {
        $rules = [
            'panel_name' => 'required|max_length[100]|is_unique[dbpanel.panel_name]'
        ];
        
        if ($this->validate($rules)) {
            $panelName = $this->request->getPost('panel_name');
            
            // Create an empty panel entry to establish the panel exists
            // We'll use a placeholder field that can be removed later
            $this->dbpanelModel->save([
                'panel_name' => $panelName,
                'field_name' => '_placeholder',
                'field_label' => 'Placeholder Field',
                'field_type' => 'input',
                'field_role' => 'requestor',
                'field_order' => 0,
                'width' => 6,
                'required' => 0,
                'bump_next_field' => 0,
                'code_table' => '',
                'length' => null
            ]);
            
            return redirect()->to('/admin/dynamicforms/form-builder/' . $panelName)
                            ->with('message', 'Panel created successfully. Start building your panel!');
        } else {
            $errors = $this->validator->getErrors();
            return redirect()->to('/admin/dynamicforms/panel-config')
                            ->with('error', implode(', ', $errors));
        }
    }
    
    public function copyPanel()
    {
        $rules = [
            'source_panel_name' => 'required|max_length[100]',
            'new_panel_name' => 'required|max_length[100]|is_unique[dbpanel.panel_name]'
        ];
        
        if ($this->validate($rules)) {
            $sourcePanelName = $this->request->getPost('source_panel_name');
            $newPanelName = $this->request->getPost('new_panel_name');
            
            // Get all fields from the source panel
            $sourceFields = $this->dbpanelModel->getPanelFields($sourcePanelName);
            
            if (empty($sourceFields)) {
                return redirect()->to('/admin/dynamicforms/panel-config')
                                ->with('error', 'Source panel not found or has no fields');
            }
            
            // Copy each field to the new panel
            foreach ($sourceFields as $field) {
                $newFieldData = [
                    'panel_name' => $newPanelName,
                    'field_name' => $field['field_name'],
                    'field_label' => $field['field_label'],
                    'field_type' => $field['field_type'],
                    'field_role' => $field['field_role'] ?? 'requestor',
                    'default_value' => $field['default_value'] ?? '',
                    'field_order' => $field['field_order'],
                    'width' => $field['width'] ?? 6,
                    'required' => $field['required'] ?? 0,
                    'bump_next_field' => $field['bump_next_field'] ?? 0,
                    'code_table' => $field['code_table'] ?? '',
                    'length' => $field['length'] ?? ''
                ];
                
                $this->dbpanelModel->save($newFieldData);
            }
            
            $fieldCount = count($sourceFields);
            return redirect()->to('/admin/dynamicforms/panel-config')
                            ->with('message', "Panel '{$newPanelName}' created successfully with {$fieldCount} fields copied from '{$sourcePanelName}'");
        } else {
            $errors = $this->validator->getErrors();
            return redirect()->to('/admin/dynamicforms/panel-config')
                            ->with('error', implode(', ', $errors));
        }
    }
    
    /**
     * Remove placeholder fields from a panel
     * This is called automatically when real fields are added
     */
    private function removePlaceholderFields($panelName)
    {
        $this->dbpanelModel->where('panel_name', $panelName)
                          ->where('field_name', '_placeholder')
                          ->delete();
    }

    public function editPanel($panelName = null)
    {
        if (!$panelName) {
            return redirect()->to('/admin/dynamicforms/panel-config')->with('error', 'Panel name is required');
        }
        
        $panelFields = $this->dbpanelModel->getPanelFields($panelName);
        
        $data = [
            'title' => 'Edit Panel: ' . $panelName,
            'panel_name' => $panelName,
            'panel_fields' => $panelFields
        ];
        
        return view('admin/dynamicforms/edit_panel', $data);
    }

    public function formBuilder($panelName = null)
    {
        if (!$panelName) {
            return redirect()->to('/admin/dynamicforms/panel-config')->with('error', 'Panel name is required');
        }
        
        $panelFields = $this->dbpanelModel->getPanelFields($panelName);
        
        // Filter out placeholder fields created during panel creation
        $panelFields = array_filter($panelFields, function($field) {
            return $field['field_name'] !== '_placeholder';
        });
        
        $data = [
            'title' => 'Panel Builder: ' . $panelName,
            'panel_name' => $panelName,
            'panel_fields' => $panelFields
        ];
        
        return view('admin/dynamicforms/form_builder', $data);
    }
    
    public function addPanelField()
    {
        $rules = [
            'panel_name' => 'required|max_length[100]',
            'field_name' => 'required|max_length[100]',
            'field_label' => 'required|max_length[100]',
            'field_type' => 'required|in_list[input,dropdown,textarea,list,datepicker,yesno,radio,checkbox]',
            'field_role' => 'required|in_list[requestor,service_staff,both,readonly]'
        ];
        
        if ($this->validate($rules)) {

            log_message('debug', 'Panel field POST data: ' . json_encode($this->request->getPost()));

            $this->dbpanelModel->save([
                'panel_name' => $this->request->getPost('panel_name'),
                'field_name' => $this->request->getPost('field_name'),
                'field_label' => $this->request->getPost('field_label'),
                'field_type' => $this->request->getPost('field_type'),
                'bump_next_field' => (int)$this->request->getPost('bump_next_field'),
                'code_table' => $this->request->getPost('code_table'),
                'length' => $this->request->getPost('length'),
                'default_value' => $this->request->getPost('default_value') ?? '',
                'field_order' => $this->request->getPost('field_order') ?? 0,
                'required' => (int)$this->request->getPost('required'),
                'width' => $this->request->getPost('width') ?? 6,
                    'field_role' => $this->request->getPost('field_role') ?? 'requestor'
            ]);
            
            return redirect()->to('/admin/dynamicforms/edit-panel/' . $this->request->getPost('panel_name'))
                            ->with('message', 'Field added successfully');
        } else {
            return redirect()->back()
                            ->with('error', 'There was a problem adding the field')
                            ->withInput()
                            ->with('validation', $this->validator);
        }
    }
    
    public function deleteField($id = null)
    {
        
        if (!$field) {
            return redirect()->back()->with('error', 'Field not found');
        }
        
        $panelName = $field['panel_name'];
        $this->dbpanelModel->delete($id);
        
        return redirect()->to('/admin/dynamicforms/edit-panel/' . $panelName)
                        ->with('message', 'Field deleted successfully');
    }

    public function updatePanelField()
    {
        $rules = [
            'field_id' => 'required|numeric',
            'panel_name' => 'required|max_length[100]',
            'field_name' => 'required|max_length[100]',
            'field_label' => 'required|max_length[100]',
            'field_type' => 'required|in_list[input,dropdown,textarea,list,datepicker,yesno,radio,checkbox]',
            'field_role' => 'required|in_list[requestor,service_staff,both,readonly]'
        ];
        
        if ($this->validate($rules)) {
            $fieldId = $this->request->getPost('field_id');
            $panelName = $this->request->getPost('panel_name');

            log_message('debug', 'Panel field update POST data: ' . json_encode($this->request->getPost()));
            
            $this->dbpanelModel->update($fieldId, [
                'field_name' => $this->request->getPost('field_name'),
                'field_label' => $this->request->getPost('field_label'),
                'field_type' => $this->request->getPost('field_type'),
                'bump_next_field' => (int)$this->request->getPost('bump_next_field'),
                'code_table' => $this->request->getPost('code_table'),
                'length' => $this->request->getPost('length'),
                'default_value' => $this->request->getPost('default_value') ?? '',
                'field_order' => $this->request->getPost('field_order'),
                'required' => (int)$this->request->getPost('required'),
                'width' => $this->request->getPost('width') ?? 6,
                'field_role' => $this->request->getPost('field_role') ?? 'both'
            ]);
            
            return redirect()->to('/admin/dynamicforms/edit-panel/' . $panelName)
                            ->with('message', 'Field updated successfully');
        } else {
            return redirect()->back()
                            ->with('error', 'There was a problem updating the field')
                            ->withInput()
                            ->with('validation', $this->validator);
        }
    }

    public function submit()
    {
        $formId = $this->request->getPost('form_id');
        $panelName = $this->request->getPost('panel_name');

        log_message('debug', 'Admin DynamicForms::submit POST data: ' . json_encode($this->request->getPost()));

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

            // Admin can edit all fields in this flow
            $raw = $this->request->getPost($fieldName);
            $otherText = $this->request->getPost($fieldName . '_other');

            // If it's an array (checkboxes), replace any 'Other' token with the provided other text
            if (is_array($raw)) {
                $normalized = [];
                foreach ($raw as $v) {
                    if (preg_match('/^others?$/i', (string)$v) && !empty($otherText)) {
                        $normalized[] = $otherText;
                    } else {
                        $normalized[] = $v;
                    }
                }
                // Persist as JSON so we can decode later when rendering/exporting
                $fieldValue = json_encode($normalized);
            } else {
                // Single value (input, radio, dropdown)
                $val = $raw ?? '';
                if (is_string($val) && preg_match('/^others?$/i', $val) && !empty($otherText)) {
                    $val = $otherText;
                }
                $fieldValue = $val;
            }

            $this->formSubmissionDataModel->insert([
                'submission_id' => $submissionId,
                'field_name' => $fieldName,
                'field_value' => $fieldValue
            ]);
        }

        return redirect()->to('/admin/dynamicforms/submissions')
                        ->with('message', 'Form submitted successfully');
    }

    public function submissions()
    {
        // Get filters
        $formId = $this->request->getGet('form_id');
        $status = $this->request->getGet('status');
        $priority = $this->request->getGet('priority');
        $search = $this->request->getGet('search');
        
        // IMPORTANT: Fix the admin check - handle null user_type
        $userType = session()->get('user_type');
        
        // Consider user with ID 1 as admin if user_type isn't set
        // Adjust this based on your application's admin user ID
        $isAdmin = (in_array($userType, ['admin', 'superuser']) || session()->get('user_id') == 1);
        
        // Debug information
        log_message('info', 'DynamicForms submissions() - User Type: ' . ($userType ?? 'null'));
        log_message('info', 'DynamicForms submissions() - User ID: ' . (session()->get('user_id') ?? 'null'));
        log_message('info', 'DynamicForms submissions() - Is Admin: ' . ($isAdmin ? 'true' : 'false'));
        
        // For admin, show all submissions by NOT filtering by user ID
        $userId = $isAdmin ? null : session()->get('user_id');
        
        // Get all forms for filter dropdown
        $forms = $this->formModel->findAll();
        
        // Get priority configurations
        $priorityModel = new \App\Models\PriorityConfigurationModel();
        $priorities = $priorityModel->getPriorityOptions();
        
        // Get submissions - use this simplified approach to avoid issues
        if ($isAdmin) {
            // For admin, get all submissions without filtering
            $submissions = $this->db->table('form_submissions fs')
                ->select('fs.*, f.code as form_code, f.description as form_description, u.full_name as submitted_by_name')
                ->join('forms f', 'f.id = fs.form_id', 'left')
                ->join('users u', 'u.id = fs.submitted_by', 'left')
                ->orderBy('fs.created_at', 'DESC')
                ->get()
                ->getResultArray();
        } else {
            // For regular users, only show their submissions
            $submissions = $this->db->table('form_submissions fs')
                ->select('fs.*, f.code as form_code, f.description as form_description, u.full_name as submitted_by_name')
                ->join('forms f', 'f.id = fs.form_id', 'left')
                ->join('users u', 'u.id = fs.submitted_by', 'left')
                ->where('fs.submitted_by', session()->get('user_id'))
                ->orderBy('fs.created_at', 'DESC')
                ->get()
                ->getResultArray();
        }
        
        // Handle missing data in results
        foreach ($submissions as &$row) {
            if (empty($row['form_code'])) $row['form_code'] = 'Unknown';
            if (empty($row['form_description'])) $row['form_description'] = 'Unknown Form';
            if (empty($row['submitted_by_name'])) $row['submitted_by_name'] = 'Unknown User';
        }
        
        // Debug: Log how many submissions we found
        log_message('info', 'DynamicForms submissions() - Found ' . count($submissions) . ' submissions');
        
        // Apply filters if specified
        if ($formId || $status || $search) {
            $filteredSubmissions = [];
            foreach ($submissions as $submission) {
                $includeSubmission = true;
                
                if ($formId && $submission['form_id'] != $formId) {
                    $includeSubmission = false;
                }
                
                if ($status && $submission['status'] != $status) {
                    $includeSubmission = false;
                }
                
                if ($priority && $submission['priority'] != $priority) {
                    $includeSubmission = false;
                }
                
                if ($search && (
                    stripos($submission['form_code'] ?? '', $search) === false &&
                    stripos($submission['form_description'] ?? '', $search) === false &&
                    stripos($submission['submitted_by_name'] ?? '', $search) === false &&
                    stripos($submission['panel_name'] ?? '', $search) === false
                )) {
                    $includeSubmission = false;
                }
                
                if ($includeSubmission) {
                    $filteredSubmissions[] = $submission;
                }
            }
            $submissions = $filteredSubmissions;
        }
        
        $data = [
            'title' => 'Form Submissions',
            'submissions' => $submissions,
            'forms' => $forms,
            'priorities' => $priorities,
            'formId' => $formId,
            'status' => $status,
            'priority' => $priority,
            'search' => $search
        ];
        
        return view('admin/dynamicforms/submissions', $data);
    }
    
    // Method to view a specific submission
    public function viewSubmission($id = null)
    {
        if (!$id) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission ID is required');
        }
        
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission not found');
        }
        
        // UPDATED: Check if user has permission to view this submission
        // Consider user with ID 1 as admin if user_type isn't set
        $userType = session()->get('user_type');
        $isAdmin = (in_array($userType, ['admin', 'superuser']) || session()->get('user_id') == 1);
        
        if (!$isAdmin && $submission['submitted_by'] != session()->get('user_id')) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'You do not have permission to view this submission');
        }
        
        // Get form details
        $form = $this->formModel->find($submission['form_id']);
        
        // Get panel fields to know the structure
        $panelFields = $this->dbpanelModel->getPanelFields($submission['panel_name']);
        
        // Get submission data
        $submissionData = $this->formSubmissionDataModel->getSubmissionDataAsArray($id);
        
        // Get submitter info
        $userModel = new \App\Models\UserModel();
        $submitter = $userModel->find($submission['submitted_by']);
        
        // Get approver info if submission is approved
        $approver = null;
        if (!empty($submission['approver_id'])) {
            $approver = $userModel->find($submission['approver_id']);
        }
        
        // Check if current user can approve submissions
        $canApprove = in_array(session()->get('user_type'), ['admin', 'superuser', 'approving_authority']) && 
                     $submission['status'] === 'submitted';
        
        // Check if current user has a signature
        $currentUser = $userModel->find(session()->get('user_id'));
        $hasSignature = !empty($currentUser['signature']);
        
        $data = [
            'title' => 'View Submission',
            'submission' => $submission,
            'form' => $form,
            'panel_fields' => $panelFields,
            'submission_data' => $submissionData,
            'submitter' => $submitter,
            'approver' => $approver,
            'canApprove' => $canApprove,
            'hasSignature' => $hasSignature,
            'currentUser' => $currentUser
        ];
        
        return view('admin/dynamicforms/view_submission', $data);
    }

    // New method to show approval form
    public function approvalForm($id = null)
    {
        if (!$id) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission ID is required');
        }
        
        // Check if user has permission to approve
        $userType = session()->get('user_type');
        
        if (!in_array($userType, ['admin', 'superuser', 'approving_authority'])) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'You do not have permission to approve submissions');
        }
        
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission not found');
        }
        
        // Check if submission is already approved or rejected
        if ($submission['status'] !== 'submitted') {
            return redirect()->to('/admin/dynamicforms/view-submission/' . $id)
                            ->with('error', 'This submission has already been ' . $submission['status']);
        }
        
        // Get form details
        $form = $this->formModel->find($submission['form_id']);
        
        // Get submitter info
        $userModel = new \App\Models\UserModel();
        $submitter = $userModel->find($submission['submitted_by']);
        
        // Get current user info for signature
        $currentUser = $userModel->find(session()->get('user_id'));
        
        $data = [
            'title' => 'Approve Submission',
            'submission' => $submission,
            'form' => $form,
            'submitter' => $submitter,
            'currentUser' => $currentUser
        ];
        
        return view('admin/dynamicforms/approve', $data);
    }
    
    public function updateStatus()
    {
        // Check if user has admin permissions
        $isAdmin = in_array(session()->get('role'), ['admin', 'superuser']);
        if (!$isAdmin) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'You do not have permission to update submission status');
        }
        
        $submissionId = $this->request->getPost('submission_id');
        $status = $this->request->getPost('status');
        
        if (!$submissionId || !in_array($status, ['approved', 'rejected'])) {
            return redirect()->back()->with('error', 'Invalid submission ID or status');
        }
        
        $submission = $this->formSubmissionModel->find($submissionId);
        if (!$submission) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission not found');
        }
        
        // Update the status
        $this->formSubmissionModel->update($submissionId, ['status' => $status]);
        
        return redirect()->to('/admin/dynamicforms/view-submission/' . $submissionId)
                        ->with('message', 'Submission status updated to ' . ucfirst($status));
    }

    // Method to export submission data as PDF or Excel
    public function exportSubmission($id = null, $format = 'pdf')
    {
        if (!$id) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission ID is required');
        }
        
        $submission = $this->formSubmissionModel->find($id);
        if (!$submission) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission not found');
        }
        
        // Check if user has permission to export this submission
        $isAdmin = in_array(session()->get('role'), ['admin', 'superuser']);
        if (!$isAdmin && $submission['submitted_by'] != session()->get('user_id')) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'You do not have permission to export this submission');
        }

        // Only allow export for completed submissions
        if (($submission['status'] ?? '') !== 'completed') {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Export is only available for completed submissions');
        }
        
        if ($format == 'pdf') {
            // Redirect to the PDF generator controller
            return redirect()->to('/pdfgenerator/generateFormPdf/' . $id);
        } else if ($format == 'excel') {
            // In a real app, you'd generate an Excel file using a library like PhpSpreadsheet
            return redirect()->back()->with('message', 'Excel export functionality would be implemented here');
        }
        
        return redirect()->back()->with('error', 'Invalid export format');
    }

    /**
     * Admin-side DOCX parsing endpoint for the panel builder.
     * Accepts a DOCX file and returns mapped content-control tags => values.
     * This mirrors Forms::uploadDocx but does not require a form code.
     */
    public function parseDocx()
    {
        $method = strtolower($this->request->getMethod());
        if ($method === 'options') {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Preflight OK',
                'csrf_name' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);
        }
        if ($method !== 'post') {
            return $this->response->setStatusCode(405)->setJSON(['error' => 'Method not allowed']);
        }

        // Basic permission: only admin or superuser can import into builder
        $userType = session()->get('user_type');
        if (!in_array($userType, ['admin', 'superuser'])) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $file = $this->request->getFile('docx');
        if (!$file || !$file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid upload']);
        }
        if (strtolower($file->getExtension()) !== 'docx') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Only DOCX files are supported']);
        }

        // Move to temp path
        $tempName = $file->getRandomName();
        $tempPath = WRITEPATH . 'temp';
        if (!is_dir($tempPath)) {
            @mkdir($tempPath, 0755, true);
        }
        $file->move($tempPath, $tempName);
        $fullPath = $tempPath . DIRECTORY_SEPARATOR . $tempName;

        $fieldValues = [];
        try {
            $zip = new \ZipArchive();
            if ($zip->open($fullPath) === true) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();
                if ($xml) {
                    $doc = new \DOMDocument();
                    $doc->preserveWhiteSpace = false;
                    $doc->loadXML($xml);
                    $xpath = new \DOMXPath($doc);
                    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                    $nodes = $xpath->query('//w:sdt');
                    foreach ($nodes as $sdt) {
                        $tagName = null;
                        $tagNode = $xpath->query('.//w:tag', $sdt)->item(0);
                        if ($tagNode && $tagNode->hasAttribute('w:val')) {
                            $tagName = trim($tagNode->getAttribute('w:val'));
                        }
                        if (!$tagName) {
                            $aliasNode = $xpath->query('.//w:alias', $sdt)->item(0);
                            if ($aliasNode && $aliasNode->hasAttribute('w:val')) {
                                $tagName = trim($aliasNode->getAttribute('w:val'));
                            }
                        }
                        if (!$tagName) continue;
                        $contentNode = $xpath->query('.//w:sdtContent', $sdt)->item(0);
                        if ($contentNode) {
                            $textParts = [];
                            $textNodes = $xpath->query('.//w:t', $contentNode);
                            foreach ($textNodes as $tn) {
                                $textParts[] = $tn->textContent;
                            }
                            $value = trim(implode('', $textParts));
                            if ($value !== '') {
                                $fieldValues[$tagName] = $value;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Admin parseDocx error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Failed to parse DOCX']);
        } finally {
            @unlink($fullPath);
        }

        return $this->response->setJSON([
            'success' => true,
            'mapped' => $fieldValues,
            'count' => count($fieldValues),
            'csrf_name' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ]);
    }
    

    public function bulkAction()
    {
        // Check if user has admin permissions
        $isAdmin = in_array(session()->get('role'), ['admin', 'superuser']);
        if (!$isAdmin) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'You do not have permission to perform bulk actions');
        }
        
        $action = $this->request->getPost('bulk_action');
        $submissionIds = $this->request->getPost('selected_submissions');
        
        if (!$action || !$submissionIds || !is_array($submissionIds)) {
            return redirect()->back()->with('error', 'No action selected or no submissions selected');
        }
        
        $count = 0;
        $status = '';
        
        if ($action == 'approve') {
            $status = 'approved';
        } else if ($action == 'reject') {
            $status = 'rejected';
        } else {
            return redirect()->back()->with('error', 'Invalid bulk action');
        }
        
        foreach ($submissionIds as $id) {
            $submission = $this->formSubmissionModel->find($id);
            if ($submission && $submission['status'] == 'submitted') {
                $this->formSubmissionModel->update($id, ['status' => $status]);
                $count++;
            }
        }
        
        return redirect()->back()->with('message', $count . ' submissions ' . $status);
    }

    public function approveSubmission()
    {
        // Check if user has permission to approve
        $userType = session()->get('user_type');
        $userId = session()->get('user_id');
        
        if (!in_array($userType, ['admin', 'superuser', 'approving_authority'])) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'You do not have permission to approve submissions');
        }
        
        $submissionId = $this->request->getPost('submission_id');
        $action = $this->request->getPost('action');
        $comments = $this->request->getPost('comments');
        
        if (!$submissionId || !in_array($action, ['approve', 'reject'])) {
            return redirect()->back()->with('error', 'Invalid submission ID or action');
        }
        
        $submission = $this->formSubmissionModel->find($submissionId);
        if (!$submission) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission not found');
        }
        
        // Check if user has an uploaded signature for approval
        $userModel = new \App\Models\UserModel();
        $currentUser = $userModel->find($userId);
        
        if ($action === 'approve' && empty($currentUser['signature'])) {
            return redirect()->back()
                            ->with('error', 'You need to upload a signature before approving forms. Please update your profile.');
        }
        
        if ($action === 'approve') {
            $this->formSubmissionModel->approveSubmission($submissionId, $userId, $comments);
            $message = 'Submission approved successfully with your signature applied';
        } else {
            $this->formSubmissionModel->rejectSubmission($submissionId, $userId, $comments);
            $message = 'Submission rejected';
        }
        
        return redirect()->to('/admin/dynamicforms/view-submission/' . $submissionId)
                        ->with('message', $message);
    }

    public function saveFormBuilder()
    {
        // Check if request is AJAX
        if (!$this->request->isAJAX()) {
            log_message('warning', 'saveFormBuilder called without AJAX - isAJAX=' . ($this->request->isAJAX() ? 'true' : 'false'));
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        // Debug: log incoming header and raw body to help diagnose CSRF 403 issues
        try {
            $incomingCsrfHeader = $this->request->getHeaderLine('X-CSRF-TOKEN');
            log_message('debug', 'saveFormBuilder incoming X-CSRF-TOKEN header: ' . $incomingCsrfHeader);
            $rawInput = $this->request->getBody();
            // Truncate raw input to avoid huge logs
            log_message('debug', 'saveFormBuilder raw body (truncated 2000 chars): ' . substr($rawInput, 0, 2000));
        } catch (\Exception $e) {
            log_message('error', 'saveFormBuilder debug log error: ' . $e->getMessage());
        }

        try {
            // Accept either JSON body or form-encoded fallback with 'payload' param
            $input = [];
            try {
                $raw = $this->request->getBody();
                if ($raw) {
                    $decoded = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $input = $decoded;
                    }
                }
            } catch (\Exception $e) {
                log_message('debug', 'saveFormBuilder safe JSON parse failed: ' . $e->getMessage());
            }

            if (empty($input)) {
                $payload = $this->request->getPost('payload');
                if ($payload) {
                    $decoded = json_decode($payload, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) $input = $decoded;
                }
            }
            $panelName = $input['panel_name'] ?? '';
            $fields = $input['fields'] ?? [];

            if (empty($panelName)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Panel name is required',
                    'csrf_name' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ]);
            }

            if (empty($fields)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'At least one field is required',
                    'csrf_name' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ]);
            }

            // Start transaction
            $this->db->transStart();

            // Delete existing fields for this panel
            $this->dbpanelModel->where('panel_name', $panelName)->delete();

            // Insert new fields
            // Get allowed fields from model to avoid inserting unexpected columns
            $allowed = $this->dbpanelModel->allowedFields ?? [];

            foreach ($fields as $index => $field) {
                $fieldData = [
                    'panel_name' => $panelName,
                    'field_name' => $field['field_name'] ?? '',
                    'field_label' => $field['field_label'] ?? '',
                    'field_type' => $field['field_type'] ?? 'input',
                    'field_role' => $field['field_role'] ?? 'requestor',
                    'required' => isset($field['required']) ? (int)$field['required'] : 0,
                    'width' => isset($field['width']) ? (int)$field['width'] : 6,
                    'field_order' => isset($field['field_order']) ? (int)$field['field_order'] : ($index + 1),
                    'bump_next_field' => isset($field['bump_next_field']) ? (int)$field['bump_next_field'] : 0,
                    'code_table' => $field['code_table'] ?? '',
                    'length' => $field['length'] ?? ''
                ];

                // Add default_value only if the column is allowed in the model
                if (in_array('default_value', $allowed)) {
                    // If this field has options (dropdown or radio), persist them as JSON
                    if (!empty($field['options']) && is_array($field['options']) && in_array($field['field_type'] ?? '', ['dropdown', 'radio'])) {
                        $fieldData['default_value'] = json_encode(array_values($field['options']));
                    } else {
                        $fieldData['default_value'] = $field['default_value'] ?? '';
                    }
                }

                // Validate required fields
                if (empty($fieldData['field_name']) || empty($fieldData['field_label'])) {
                    $this->db->transRollback();
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Field name and label are required for all fields',
                        'csrf_name' => csrf_token(),
                        'csrf_hash' => csrf_hash()
                    ]);
                }

                // Debug: log the exact data being inserted to help diagnose type/option issues
                try {
                    log_message('debug', 'saveFormBuilder inserting field: ' . json_encode($fieldData));
                } catch (\Exception $e) {
                    // ignore logging failures
                }

                try {
                    $this->dbpanelModel->insert($fieldData);
                } catch (\Exception $ex) {
                    // Capture DB exception details and rollback transaction for clearer diagnostics
                    log_message('error', 'saveFormBuilder insert exception: ' . $ex->getMessage() . ' data: ' . json_encode($fieldData));
                    $this->db->transRollback();
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Database insert error',
                        'csrf_name' => csrf_token(),
                        'csrf_hash' => csrf_hash()
                    ]);
                }
            }

            // Complete transaction
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                // Log DB error details for debugging
                $error = $this->db->error();
                $lastQuery = '';
                try {
                    $lastQuery = $this->db->getLastQuery();
                } catch (\Exception $e) {
                    // ignore
                }

                log_message('error', 'saveFormBuilder DB transaction failed: ' . json_encode($error) . ' last_query: ' . $lastQuery);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Database error occurred',
                    'csrf_name' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Form saved successfully',
                'redirect' => base_url('admin/dynamicforms/panel-config'),
                'csrf_name' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Form builder save error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred while saving',
                'csrf_name' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);
        }
    }

    public function reorderFields()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $input = [];
            try {
                $raw = $this->request->getBody();
                if ($raw) {
                    $decoded = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $input = $decoded;
                    }
                }
            } catch (\Exception $e) {
                log_message('debug', 'reorderFields safe JSON parse failed: ' . $e->getMessage());
            }

            $fieldIds = $input['field_ids'] ?? [];

            if (empty($fieldIds)) {
                return $this->response->setJSON(['success' => false, 'message' => 'No fields to reorder']);
            }

            // Start transaction
            $this->db->transStart();

            // Update field order
            foreach ($fieldIds as $index => $fieldId) {
                $this->dbpanelModel->update($fieldId, ['field_order' => $index + 1]);
            }

            // Complete transaction
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->response->setJSON(['success' => false, 'message' => 'Database error occurred']);
            }

            return $this->response->setJSON(['success' => true, 'message' => 'Field order updated']);

        } catch (\Exception $e) {
            log_message('error', 'Field reorder error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'An error occurred while reordering']);
        }
    }
    
    public function createForm()
    {
        if (!$this->request->getMethod() === 'POST') {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Invalid request method');
        }
        
        $validation = \Config\Services::validation();
        $validation->setRules([
            'code' => 'required|max_length[50]|is_unique[forms.code]',
            'description' => 'required|max_length[255]',
            'panel_name' => 'permit_empty|max_length[255]',
            'office_id' => 'required|numeric|is_not_unique[offices.id]'
        ]);
        
        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->to('/admin/dynamicforms')
                           ->withInput()
                           ->with('error', 'Validation failed: ' . implode(', ', $validation->getErrors()));
        }
        
        $data = [
            'code' => $this->request->getPost('code'),
            'description' => $this->request->getPost('description'),
            'panel_name' => $this->request->getPost('panel_name') ?: null,
            'office_id' => $this->request->getPost('office_id')
        ];
        
        if ($this->formModel->insert($data)) {
            return redirect()->to('/admin/dynamicforms')->with('success', 'Form created successfully');
        } else {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Failed to create form');
        }
    }
    
    public function updateForm()
    {
        if (!$this->request->getMethod() === 'POST') {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Invalid request method');
        }
        
        $formId = $this->request->getPost('form_id');
        if (!$formId) {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Form ID is required');
        }
        
        $validation = \Config\Services::validation();
        $validation->setRules([
            'code' => "required|max_length[50]|is_unique[forms.code,id,{$formId}]",
            'description' => 'required|max_length[255]',
            'panel_name' => 'permit_empty|max_length[255]',
            'office_id' => 'required|numeric|is_not_unique[offices.id]'
        ]);
        
        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->to('/admin/dynamicforms')
                           ->withInput()
                           ->with('error', 'Validation failed: ' . implode(', ', $validation->getErrors()));
        }
        
        $data = [
            'code' => $this->request->getPost('code'),
            'description' => $this->request->getPost('description'),
            'panel_name' => $this->request->getPost('panel_name') ?: null,
            'office_id' => $this->request->getPost('office_id')
        ];
        
        if ($this->formModel->update($formId, $data)) {
            return redirect()->to('/admin/dynamicforms')->with('success', 'Form updated successfully');
        } else {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Failed to update form');
        }
    }
    
    public function deleteForm()
    {
        // Check if user is admin or superuser
        if (!in_array(session('user_type'), ['admin', 'superuser'])) {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Unauthorized access. Admin or Superuser privileges required.');
        }
        
        if (!$this->request->getMethod() === 'POST') {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Invalid request method');
        }
        
        $formId = $this->request->getPost('form_id');
        if (!$formId) {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Form ID is required');
        }
        
        $form = $this->formModel->find($formId);
        if (!$form) {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Form not found');
        }
        
        // Check if there are any submissions for this form
        $submissionCount = $this->formSubmissionModel->where('form_id', $formId)->countAllResults();
        if ($submissionCount > 0) {
            return redirect()->to('/admin/dynamicforms')
                           ->with('error', 'Cannot delete form. There are ' . $submissionCount . ' submissions associated with this form.');
        }
        
        if ($this->formModel->delete($formId)) {
            return redirect()->to('/admin/dynamicforms')->with('success', 'Form "' . $form['code'] . '" deleted successfully');
        } else {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Failed to delete form');
        }
    }
    
    public function deletePanel()
    {
        // Check if user is admin or superuser
        if (!in_array(session('user_type'), ['admin', 'superuser'])) {
            return redirect()->to('/admin/dynamicforms/panel-config')->with('error', 'Unauthorized access. Admin or Superuser privileges required.');
        }
        
        if (!$this->request->getMethod() === 'POST') {
            return redirect()->to('/admin/dynamicforms/panel-config')->with('error', 'Invalid request method');
        }
        
        $panelName = $this->request->getPost('panel_name');
        if (!$panelName) {
            return redirect()->to('/admin/dynamicforms/panel-config')->with('error', 'Panel name is required');
        }
        
        // Check if any forms are using this panel
        $formsUsingPanel = $this->formModel->where('panel_name', $panelName)->findAll();
        if (!empty($formsUsingPanel)) {
            $formCodes = array_column($formsUsingPanel, 'code');
            return redirect()->to('/admin/dynamicforms/panel-config')
                           ->with('error', 'Cannot delete panel. It is being used by forms: ' . implode(', ', $formCodes));
        }
        
        // Delete all fields for this panel
        $deleted = $this->dbpanelModel->where('panel_name', $panelName)->delete();
        
        if ($deleted) {
            return redirect()->to('/admin/dynamicforms/panel-config')->with('success', 'Panel "' . $panelName . '" deleted successfully');
        } else {
            return redirect()->to('/admin/dynamicforms/panel-config')->with('error', 'Failed to delete panel or panel not found');
        }
    }
    
    /**
     * Update priority for a form submission
     */
    public function updatePriority()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method']);
        }
        
        $submissionId = $this->request->getPost('submission_id');
        $priority = $this->request->getPost('priority');
        
        if (!$submissionId || !$priority) {
            return $this->response->setJSON(['success' => false, 'message' => 'Submission ID and priority are required']);
        }
        
        // Validate priority exists
        $priorityModel = new \App\Models\PriorityConfigurationModel();
        $validPriority = $priorityModel->where('priority_level', $priority)->first();
        
        if (!$validPriority) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid priority level']);
        }
        
        // Update the submission
        $submissionModel = new \App\Models\FormSubmissionModel();
        $result = $submissionModel->update($submissionId, ['priority' => $priority]);
        
        if ($result) {
            return $this->response->setJSON([
                'success' => true, 
                'message' => 'Priority updated successfully',
                'priority_label' => $validPriority['priority_level'],
                'priority_color' => $validPriority['priority_color']
            ]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to update priority']);
        }
    }
}
