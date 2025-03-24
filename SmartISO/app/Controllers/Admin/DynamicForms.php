<?php

namespace App\Controllers\Admin;

use CodeIgniter\Model;
use App\Controllers\BaseController;
use App\Models\DbpanelModel;
use App\Models\FormModel;
use App\Models\DepartmentModel;
use App\Models\FormSubmissionModel;
use App\Models\FormSubmissionDataModel;

class DynamicForms extends BaseController
{
    protected $dbpanelModel;
    protected $formModel;
    protected $departmentModel;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->dbpanelModel = new DbpanelModel();
        $this->formModel = new FormModel();
        $this->departmentModel = new DepartmentModel();
        $this->formSubmissionModel = new FormSubmissionModel();
        $this->formSubmissionDataModel = new FormSubmissionDataModel();
    }
    
    public function index()
    {
        $data = [
            'title' => 'Dynamic Forms',
            'forms' => $this->formModel->findAll()
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
            'title' => 'Panel Configuration',
            'panels' => $this->dbpanelModel->getPanelNames()
        ];
        
        return view('admin/dynamicforms/panel_config', $data);
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
    
    public function addPanelField()
    {
        $rules = [
            'panel_name' => 'required|max_length[100]',
            'field_name' => 'required|max_length[100]',
            'field_label' => 'required|max_length[100]',
            'field_type' => 'required|in_list[input,dropdown,textarea,datepicker]'
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
                'field_order' => $this->request->getPost('field_order') ?? 0,
                'required' => (int)$this->request->getPost('required'),
                'width' => $this->request->getPost('width') ?? 6
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
        $field = $this->dbpanelModel->find($id);
        
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
            'field_type' => 'required|in_list[input,dropdown,textarea,datepicker]'
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
                'field_order' => $this->request->getPost('field_order'),
                'required' => (int)$this->request->getPost('required'),
                'width' => $this->request->getPost('width') ?? 6
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
        
        return redirect()->to('/admin/dynamicforms/submissions')
                        ->with('message', 'Form submitted successfully');
    }

    public function submissions()
    {
        // Get filters
        $formId = $this->request->getGet('form_id');
        $status = $this->request->getGet('status');
        $search = $this->request->getGet('search');
        
        // IMPORTANT: Fix the admin check - handle null role
        $userRole = session()->get('role');
        
        // Consider user with ID 1 as admin if role isn't set
        // Adjust this based on your application's admin user ID
        $isAdmin = ($userRole === 'admin' || $userRole === 'superuser' || session()->get('user_id') == 1);
        
        // For admin, show all submissions by NOT filtering by user ID
        $userId = $isAdmin ? null : session()->get('user_id');
        
        // Get all forms for filter dropdown
        $forms = $this->formModel->findAll();
        
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
            'formId' => $formId,
            'status' => $status,
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
        // Consider user with ID 1 as admin if role isn't set
        $userRole = session()->get('role');
        $isAdmin = ($userRole === 'admin' || $userRole === 'superuser' || session()->get('user_id') == 1);
        
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
}
