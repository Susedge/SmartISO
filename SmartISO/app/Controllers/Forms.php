<?php

namespace App\Controllers;

use App\Models\FormModel;
use App\Models\DbpanelModel;
use App\Models\FormSubmissionModel;
use App\Models\FormSubmissionDataModel;
use App\Models\DepartmentModel;
use App\Models\UserModel;

class Forms extends BaseController
{
    protected $formModel;
    protected $dbpanelModel;
    protected $formSubmissionModel;
    protected $formSubmissionDataModel;
    protected $departmentModel;
    protected $userModel;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();

        $this->formModel = new FormModel();
        $this->dbpanelModel = new DbpanelModel();
        $this->formSubmissionModel = new FormSubmissionModel();
        $this->formSubmissionDataModel = new FormSubmissionDataModel();
        $this->departmentModel = new DepartmentModel();
        $this->userModel = new UserModel();
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
    
    public function pendingApproval()
    {
        // Only for approving_authority
        if (session()->get('user_type') !== 'approving_authority' && 
            session()->get('user_type') !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        $data = [
            'title' => 'Forms Pending Approval',
            'submissions' => $this->formSubmissionModel->getPendingApprovals()
        ];
        
        return view('forms/pending_approval', $data);
    }
    
    public function pendingService()
    {
        $userType = session()->get('user_type');
        
        if ($userType !== 'service_staff' && $userType !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        // Get approved forms that have not been serviced yet
        $builder = $this->db->table('form_submissions fs');
        $builder->select('fs.*, f.code as form_code, f.description as form_description, 
                        u.full_name as requestor_name, d.description as department_name')
                ->join('forms f', 'f.id = fs.form_id', 'left')
                ->join('users u', 'u.id = fs.submitted_by', 'left')
                ->join('users submitter', 'submitter.id = fs.submitted_by', 'left')
                ->join('departments d', 'd.id = submitter.department_id', 'left')
                ->where('fs.status', 'approved')
                ->where('fs.service_staff_id IS NULL');
                
        $submissions = $builder->get()->getResultArray();
        
        $data = [
            'title' => 'Forms Pending Service',
            'submissions' => $submissions
        ];
        
        return view('forms/pending_service', $data);
    }
    
    public function pendingRequestorSignature()
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        // For requestors, only show their forms
        if ($userType === 'requestor') {
            $builder = $this->db->table('form_submissions fs');
            $builder->select('fs.*, f.code as form_code, f.description as form_description')
                ->join('forms f', 'f.id = fs.form_id', 'left')
                ->where('fs.status', 'approved')
                ->where('fs.submitted_by', $userId)
                ->where('fs.service_staff_id IS NOT NULL')
                ->where('fs.service_staff_signature_date IS NOT NULL')
                ->where('fs.requestor_signature_date IS NULL')
                ->orderBy('fs.service_staff_signature_date', 'ASC');
            
            $submissions = $builder->get()->getResultArray();
        } else {
            // For admin/staff, show all
            $submissions = $this->formSubmissionModel->getPendingRequestorSignature();
        }
        
        $data = [
            'title' => 'Forms Awaiting Final Signature',
            'submissions' => $submissions
        ];
        
        return view('forms/pending_signature', $data);
    }
    
    public function completedForms()
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        // For requestors, only show their own completed forms
        if ($userType === 'requestor') {
            $submissions = $this->formSubmissionModel->where('submitted_by', $userId)
                                                ->where('status', 'completed')
                                                ->findAll();
        } else {
            // For approvers and service staff, show all completed forms
            $submissions = $this->formSubmissionModel->where('status', 'completed')
                                                ->findAll();
        }
        
        // Get additional details for each submission
        $submissionsWithDetails = [];
        foreach ($submissions as $submission) {
            // Get form details
            $form = $this->formModel->find($submission['form_id']);
            $submissionWithDetails = $submission;
            
            if ($form) {
                $submissionWithDetails['form_code'] = $form['code'];
                $submissionWithDetails['form_description'] = $form['description'];
            } else {
                $submissionWithDetails['form_code'] = 'Unknown';
                $submissionWithDetails['form_description'] = 'Unknown Form';
            }
            
            $submissionsWithDetails[] = $submissionWithDetails;
        }
        
        $data = [
            'title' => 'Completed Forms',
            'submissions' => $submissionsWithDetails
        ];
        
        return view('forms/completed', $data);
    }
    
    public function viewSubmission($id)
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission) {
            return redirect()->to('/forms/my-submissions')
                            ->with('error', 'Submission not found');
        }
        
        // Check permissions - allow view based on role
        $canView = false;
        
        if ($userType === 'admin' || $userType === 'approving_authority' || $userType === 'service_staff') {
            $canView = true;
        } else if ($userType === 'requestor' && $submission['submitted_by'] == $userId) {
            $canView = true;
        }

        if (!$canView) {
            return redirect()->to('/dashboard')
                            ->with('error', 'You don\'t have permission to view this submission');
        }
        
        // Get form details
        $form = $this->formModel->find($submission['form_id']);
        
        // Get panel fields
        $panelFields = $this->dbpanelModel->getPanelFields($submission['panel_name']);
        
        // Get submission data
        $submissionData = $this->formSubmissionDataModel->getSubmissionDataAsArray($id);
        
        // Get approver info if submission is approved
        $approver = null;
        if (!empty($submission['approver_id'])) {
            $approver = $this->userModel->find($submission['approver_id']);
        }
        
        // Get service staff info if assigned
        $serviceStaff = null;
        if (!empty($submission['service_staff_id'])) {
            $serviceStaff = $this->userModel->find($submission['service_staff_id']);
        }
        
        // Determine if current user can take action on this form
        $canApprove = ($userType === 'approving_authority' && $submission['status'] === 'submitted');
        $canService = ($userType === 'service_staff' && $submission['status'] === 'approved' && empty($submission['service_staff_id']));
        $canSignCompletion = ($userType === 'requestor' && $submission['submitted_by'] == $userId && 
                             !empty($submission['service_staff_signature_date']) && empty($submission['requestor_signature_date']));
        
        // Check if user has signature
        $currentUser = $this->userModel->find($userId);
        $hasSignature = !empty($currentUser['signature']);
        
        $data = [
            'title' => 'View Submission',
            'submission' => $submission,
            'form' => $form,
            'panel_fields' => $panelFields,
            'submission_data' => $submissionData,
            'approver' => $approver,
            'service_staff' => $serviceStaff,
            'canApprove' => $canApprove,
            'canService' => $canService,
            'canSignCompletion' => $canSignCompletion,
            'hasSignature' => $hasSignature,
            'current_user' => $currentUser
        ];
        
        return view('forms/view_submission', $data);
    }
    
    public function signForm($id)
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission) {
            return redirect()->to('/dashboard')
                            ->with('error', 'Submission not found');
        }
        
        // Check if user has uploaded signature
        $currentUser = $this->userModel->find($userId);
        if (empty($currentUser['signature'])) {
            return redirect()->to('/profile')
                            ->with('error', 'You need to upload your signature before signing forms');
        }
        
        // Check permissions based on user role
        if ($userType === 'requestor') {
            // Requestor can only sign their own forms
            if ($submission['submitted_by'] != $userId) {
                return redirect()->to('/forms/my-submissions')
                                ->with('error', 'You do not have permission to sign this form');
            }
            
            // Check if form has been serviced by staff and ready for final signature
            if ($submission['service_staff_id'] === null || $submission['service_staff_signature_date'] === null) {
                return redirect()->to('/forms/submission/' . $id)
                                ->with('error', 'This form is not ready for your signature yet');
            }
            
            // Record requestor signature date
            $this->formSubmissionModel->markAsCompleted($id);
            
            return redirect()->to('/forms/submission/' . $id)
                            ->with('message', 'Form signed successfully and marked as completed');
                            
        } elseif ($userType === 'approving_authority') {
            // Approver can only sign forms with status 'submitted'
            if ($submission['status'] !== 'submitted') {
                return redirect()->to('/forms/pending-approval')
                                ->with('error', 'This form cannot be signed at this time');
            }
            
            // Get approval comment
            $comments = $this->request->getPost('approval_comments') ?? '';
            
            // Record approver signature and update status
            $this->formSubmissionModel->approveSubmission($id, $userId, $comments);
            
            return redirect()->to('/forms/pending-approval')
                            ->with('message', 'Form approved and signed successfully');
                            
        } elseif ($userType === 'service_staff') {
            // Service staff can only sign approved forms
            if ($submission['status'] !== 'approved' || !empty($submission['service_staff_id'])) {
                return redirect()->to('/forms/pending-service')
                                ->with('error', 'This form cannot be processed at this time');
            }
            
            // Get service notes
            $notes = $this->request->getPost('service_notes') ?? '';
            
            // Record service staff signature
            $this->formSubmissionModel->markAsServiced($id, $userId, $notes);
            
            return redirect()->to('/forms/pending-service')
                            ->with('message', 'Work completed and form signed successfully');
        }
        
        return redirect()->back()->with('error', 'Unauthorized action');
    }
    
    public function rejectForm($id)
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        if ($userType !== 'approving_authority' && $userType !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission || $submission['status'] !== 'submitted') {
            return redirect()->to('/forms/pending-approval')
            ->with('error', 'Form not found or cannot be rejected');
        }
        
        $reason = $this->request->getPost('reject_reason');
        
        if (empty($reason)) {
            return redirect()->back()
                            ->with('error', 'Please provide a reason for rejection');
        }
        
        // Record rejection
        $this->formSubmissionModel->rejectSubmission($id, $userId, $reason);
        
        return redirect()->to('/forms/pending-approval')
                        ->with('message', 'Form rejected');
    }
    
    public function approveForm($id)
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        if ($userType !== 'approving_authority' && $userType !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission || $submission['status'] !== 'submitted') {
            return redirect()->to('/forms/pending-approval')
                            ->with('error', 'Form not found or cannot be approved');
        }
        
        // Get form details
        $form = $this->formModel->find($submission['form_id']);
        
        // Get panel fields
        $panelFields = $this->dbpanelModel->getPanelFields($submission['panel_name']);
        
        // Get submission data
        $submissionData = $this->formSubmissionDataModel->getSubmissionDataAsArray($id);
        
        // Get requestor details
        $requestor = $this->userModel->find($submission['submitted_by']);
        
        // Check if user has signature
        $currentUser = $this->userModel->find($userId);
        $hasSignature = !empty($currentUser['signature']);
        
        $data = [
            'title' => 'Approve Form',
            'submission' => $submission,
            'form' => $form,
            'panel_fields' => $panelFields,
            'submission_data' => $submissionData,
            'requestor' => $requestor,
            'hasSignature' => $hasSignature,
            'current_user' => $currentUser
        ];
        
        return view('forms/approval_form', $data);
    }

    /**
     * Show service form
     */
    public function serviceForm($id)
    {
        $userType = session()->get('user_type');
        
        if ($userType !== 'service_staff' && $userType !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission || $submission['status'] !== 'approved' || !empty($submission['service_staff_id'])) {
            return redirect()->to('/forms/pending-service')
                        ->with('error', 'Form not found or cannot be serviced');
        }
        
        // Get form details
        $form = $this->formModel->find($submission['form_id']);
        
        // Get requestor details
        $userModel = new \App\Models\UserModel();
        $requestor = $userModel->find($submission['submitted_by']);
        
        // Get panel fields
        $panelFields = $this->dbpanelModel->getPanelFields($submission['panel_name']);
        
        // Get submission data
        $submissionData = $this->formSubmissionDataModel->getSubmissionDataAsArray($id);
        
        $data = [
            'title' => 'Service Form',
            'submission' => $submission,
            'form' => $form,
            'requestor' => $requestor,
            'panel_fields' => $panelFields,
            'submission_data' => $submissionData
        ];
        
        return view('forms/service_form', $data);
    }

    
    public function finalSignForm($id)
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        if ($userType !== 'requestor') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission || $submission['submitted_by'] != $userId ||
            empty($submission['service_staff_id']) || empty($submission['service_staff_signature_date']) ||
            !empty($submission['requestor_signature_date'])) {
            return redirect()->to('/forms/pending-signature')
                            ->with('error', 'Form not found or cannot be signed');
        }
        
        // Get form details
        $form = $this->formModel->find($submission['form_id']);
        
        // Get panel fields
        $panelFields = $this->dbpanelModel->getPanelFields($submission['panel_name']);
        
        // Get submission data
        $submissionData = $this->formSubmissionDataModel->getSubmissionDataAsArray($id);
        
        // Get service staff details
        $serviceStaff = $this->userModel->find($submission['service_staff_id']);
        
        // Check if user has signature
        $currentUser = $this->userModel->find($userId);
        $hasSignature = !empty($currentUser['signature']);
        
        $data = [
            'title' => 'Confirm Completion',
            'submission' => $submission,
            'form' => $form,
            'panel_fields' => $panelFields,
            'submission_data' => $submissionData,
            'service_staff' => $serviceStaff,
            'hasSignature' => $hasSignature,
            'current_user' => $currentUser
        ];
        
        return view('forms/final_sign_form', $data);
    }
    
    public function confirmService($id)
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        if ($userType !== 'requestor') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission || $submission['submitted_by'] != $userId ||
            empty($submission['service_staff_id']) || empty($submission['service_staff_signature_date']) ||
            !empty($submission['requestor_signature_date'])) {
            return redirect()->to('/forms/pending-signature')
                            ->with('error', 'Form not found or cannot be signed');
        }
        
        // Check if user has uploaded signature
        $currentUser = $this->userModel->find($userId);
        if (empty($currentUser['signature'])) {
            return redirect()->to('/profile')
                            ->with('error', 'You need to upload your signature before confirming completion');
        }
        
        // Mark as completed
        $this->formSubmissionModel->markAsCompleted($id);
        
        return redirect()->to('/forms/completed')
                        ->with('message', 'Form signed and marked as completed successfully');
    }
    
    public function uploadSignature()
    {
        $userId = session()->get('user_id');
        
        // Validate file upload
        $validationRules = [
            'signature' => [
                'label' => 'Signature',
                'rules' => 'uploaded[signature]|max_size[signature,1024]|mime_in[signature,image/png,image/jpeg]'
            ]
        ];
        
        if (!$this->validate($validationRules)) {
            return redirect()->back()
                ->with('error', $this->validator->getErrors()['signature'] ?? 'Invalid signature file');
        }
        
        $file = $this->request->getFile('signature');
        
        if (!$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'Invalid file upload');
        }
        
        // Upload signature
        try {
            $this->userModel->setSignature($userId, $file);
            return redirect()->to('/profile')
                            ->with('message', 'Signature uploaded successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error uploading signature: ' . $e->getMessage());
        }
    }

    public function servicedByMe()
    {
        $userId = session()->get('user_id');
        
        // Ensure user is service staff
        if (session()->get('user_type') !== 'service_staff') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        // Get forms serviced by this user
        $submissions = $this->formSubmissionModel->where('service_staff_id', $userId)
                                            ->findAll();
        
        // Enhance submissions with form details
        $submissionsWithDetails = [];
        foreach ($submissions as $submission) {
            // Get form details
            $form = $this->formModel->find($submission['form_id']);
            $submissionWithDetails = $submission;
            
            if ($form) {
                $submissionWithDetails['form_code'] = $form['code'];
                $submissionWithDetails['form_description'] = $form['description'];
            } else {
                $submissionWithDetails['form_code'] = 'Unknown';
                $submissionWithDetails['form_description'] = 'Unknown Form';
            }
            
            // Get requestor details
            $userModel = new \App\Models\UserModel();
            $requestor = $userModel->find($submission['submitted_by']);
            if ($requestor) {
                $submissionWithDetails['requestor_name'] = $requestor['full_name'];
            } else {
                $submissionWithDetails['requestor_name'] = 'Unknown User';
            }
            
            $submissionsWithDetails[] = $submissionWithDetails;
        }
        
        $data = [
            'title' => 'Forms Serviced By Me',
            'submissions' => $submissionsWithDetails
        ];
        
        return view('forms/serviced_by_me', $data);
    }

        /**
     * Shows forms that the current user has approved
     */
    public function approvedByMe()
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        if ($userType !== 'approving_authority' && $userType !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        // Get forms approved by this user
        $submissions = $this->formSubmissionModel->where('approver_id', $userId)
                                            ->where('status', 'approved')
                                            ->findAll();
        
        // Enhance submissions with form details
        $submissionsWithDetails = [];
        foreach ($submissions as $submission) {
            // Get form details
            $form = $this->formModel->find($submission['form_id']);
            $submissionWithDetails = $submission;
            
            if ($form) {
                $submissionWithDetails['form_code'] = $form['code'];
                $submissionWithDetails['form_description'] = $form['description'];
            } else {
                $submissionWithDetails['form_code'] = 'Unknown';
                $submissionWithDetails['form_description'] = 'Unknown Form';
            }
            
            // Get requestor details
            $requestor = $this->userModel->find($submission['submitted_by']);
            if ($requestor) {
                $submissionWithDetails['requestor_name'] = $requestor['full_name'];
            } else {
                $submissionWithDetails['requestor_name'] = 'Unknown User';
            }
            
            $submissionsWithDetails[] = $submissionWithDetails;
        }
        
        $data = [
            'title' => 'Forms Approved By Me',
            'submissions' => $submissionsWithDetails
        ];
        
        return view('forms/approved_by_me', $data);
    }

    /**
     * Shows forms that the current user has rejected
     */
    public function rejectedByMe()
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        if ($userType !== 'approving_authority' && $userType !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        // Get forms rejected by this user
        $submissions = $this->formSubmissionModel->where('approver_id', $userId)
                                            ->where('status', 'rejected')
                                            ->findAll();
        
        // Enhance submissions with form details
        $submissionsWithDetails = [];
        foreach ($submissions as $submission) {
            // Get form details
            $form = $this->formModel->find($submission['form_id']);
            $submissionWithDetails = $submission;
            
            if ($form) {
                $submissionWithDetails['form_code'] = $form['code'];
                $submissionWithDetails['form_description'] = $form['description'];
            } else {
                $submissionWithDetails['form_code'] = 'Unknown';
                $submissionWithDetails['form_description'] = 'Unknown Form';
            }
            
            // Get requestor details
            $requestor = $this->userModel->find($submission['submitted_by']);
            if ($requestor) {
                $submissionWithDetails['requestor_name'] = $requestor['full_name'];
            } else {
                $submissionWithDetails['requestor_name'] = 'Unknown User';
            }
            
            $submissionsWithDetails[] = $submissionWithDetails;
        }
        
        $data = [
            'title' => 'Forms Rejected By Me',
            'submissions' => $submissionsWithDetails
        ];
        
        return view('forms/rejected_by_me', $data);
    }

    /**
     * Handles form approval submission
     */
    public function submitApproval()
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        if ($userType !== 'approving_authority' && $userType !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        $submissionId = $this->request->getPost('submission_id');
        $comments = $this->request->getPost('approval_comments') ?? '';
        
        $submission = $this->formSubmissionModel->find($submissionId);
        
        if (!$submission || $submission['status'] !== 'submitted') {
            return redirect()->to('/forms/pending-approval')
                            ->with('error', 'Form not found or cannot be approved');
        }
        
        // Record approver signature and update status
        // Here we use the simple update method to avoid database column issues
        $updateData = [
            'status' => 'approved',
            'approver_id' => $userId
        ];
        
        // Add approved_at if the column exists
        if ($this->db->fieldExists('approved_at', 'form_submissions')) {
            $updateData['approved_at'] = date('Y-m-d H:i:s');
        }
        
        // Add approval_comments if the column exists
        if ($this->db->fieldExists('approval_comments', 'form_submissions')) {
            $updateData['approval_comments'] = $comments;
        }
        
        $this->formSubmissionModel->update($submissionId, $updateData);
        
        return redirect()->to('/forms/pending-approval')
                        ->with('message', 'Form approved successfully');
    }

    /**
     * Handles form rejection submission
     */
    public function submitRejection()
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        if ($userType !== 'approving_authority' && $userType !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        $submissionId = $this->request->getPost('submission_id');
        $reason = $this->request->getPost('reject_reason');
        
        if (empty($reason)) {
            return redirect()->back()
                            ->with('error', 'Please provide a reason for rejection');
        }
        
        $submission = $this->formSubmissionModel->find($submissionId);
        
        if (!$submission || $submission['status'] !== 'submitted') {
            return redirect()->to('/forms/pending-approval')
                            ->with('error', 'Form not found or cannot be rejected');
        }
        
        // Record rejection
        // Here we use the simple update method to avoid database column issues
        $updateData = [
            'status' => 'rejected',
            'approver_id' => $userId
        ];
        
        // Add rejected_reason if the column exists
        if ($this->db->fieldExists('rejected_reason', 'form_submissions')) {
            $updateData['rejected_reason'] = $reason;
        } else if ($this->db->fieldExists('rejection_reason', 'form_submissions')) {
            $updateData['rejection_reason'] = $reason;
        }
        
        $this->formSubmissionModel->update($submissionId, $updateData);
        
        return redirect()->to('/forms/pending-approval')
                        ->with('message', 'Form rejected successfully');
    }

    public function submitService()
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        if ($userType !== 'service_staff' && $userType !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        $submissionId = $this->request->getPost('submission_id');
        $notes = $this->request->getPost('service_notes') ?? '';
        
        $submission = $this->formSubmissionModel->find($submissionId);
        
        if (!$submission || $submission['status'] !== 'approved' || !empty($submission['service_staff_id'])) {
            return redirect()->to('/forms/pending-service')
                            ->with('error', 'Form not found or cannot be serviced');
        }
        
        // Record service staff signature
        $updateData = [
            'service_staff_id' => $userId,
            'status' => 'completed', // Update status to completed
            'completed' => 1 // Add completed flag
        ];
        
        // Add service_staff_signature_date if the column exists
        if ($this->db->fieldExists('service_staff_signature_date', 'form_submissions')) {
            $updateData['service_staff_signature_date'] = date('Y-m-d H:i:s');
        }
        
        // Add completed_date if the column exists
        if ($this->db->fieldExists('completed_date', 'form_submissions')) {
            $updateData['completed_date'] = date('Y-m-d H:i:s');
        } elseif ($this->db->fieldExists('completed_at', 'form_submissions')) {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }
        
        // Add service_notes if the column exists
        if ($this->db->fieldExists('service_notes', 'form_submissions')) {
            $updateData['service_notes'] = $notes;
        }
        
        $this->formSubmissionModel->update($submissionId, $updateData);
        
        return redirect()->to('/forms/pending-service')
                        ->with('message', 'Service recorded successfully');
    }    

    public function export($id, $format = 'pdf')
    {
        if ($format == 'pdf') {
            // Redirect to the PDF generator controller
            return redirect()->to('/pdfgenerator/generateFormPdf/' . $id);
        } else if ($format == 'excel') {
            // In a real app, you'd generate an Excel file using a library like PhpSpreadsheet
            // For this example, we'll just return a message
            return redirect()->back()->with('message', 'Excel export functionality would be implemented here');
        }
        
        return redirect()->back()->with('error', 'Invalid export format');
    }
    


}

