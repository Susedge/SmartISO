<?php

namespace App\Controllers;

use App\Models\FormModel;
use App\Models\DbpanelModel;
use App\Models\FormSubmissionModel;
use App\Models\FormSubmissionDataModel;
use App\Models\DepartmentModel;
use App\Models\UserModel;
use App\Models\OfficeModel;
use App\Models\PriorityConfigurationModel;

class Forms extends BaseController
{
    protected $formModel;
    protected $dbpanelModel;
    protected $formSubmissionModel;
    protected $formSubmissionDataModel;
    protected $departmentModel;
    protected $userModel;
    protected $officeModel;
    protected $priorityModel;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();

        $this->formModel = new FormModel();
        $this->dbpanelModel = new DbpanelModel();
        $this->formSubmissionModel = new FormSubmissionModel();
        $this->formSubmissionDataModel = new FormSubmissionDataModel();
        $this->departmentModel = new DepartmentModel();
        $this->userModel = new UserModel();
        $this->priorityModel = new PriorityConfigurationModel();
        $this->officeModel = new OfficeModel();
    }

    /**
     * Return a Request instance, falling back to the global service when
     * the controller property is not populated (e.g. in unit tests).
     */
    protected function getRequest()
    {
        return $this->request ?? \Config\Services::request();
    }
    
    public function index()
    {
    // Get office filter from request (use fallback when running under tests)
    $req = $this->getRequest();
    $selectedOffice = $req->getGet('office');
        
        // Get all active offices for the dropdown if model available
        $offices = [];
        if (isset($this->officeModel) && method_exists($this->officeModel, 'getActiveOffices')) {
            $offices = $this->officeModel->getActiveOffices();
        }

        // Get forms based on office filter; allow unit tests to set a simple formModel stub
        $forms = [];
        if (isset($this->formModel)) {
            if ($selectedOffice && method_exists($this->formModel, 'getFormsByOfficeWithOffice')) {
                $forms = $this->formModel->getFormsByOfficeWithOffice($selectedOffice);
            } elseif (method_exists($this->formModel, 'getFormsWithOffice')) {
                $forms = $this->formModel->getFormsWithOffice();
            } elseif (method_exists($this->formModel, 'findAll')) {
                // Fallback for simple test stubs
                $forms = $this->formModel->findAll();
            }
        }
        
        $data = [
            'title' => 'Available Forms',
            'forms' => $forms,
            'offices' => $offices,
            'selectedOffice' => $selectedOffice
        ];
        
        return view('forms/index', $data);
    }
    
    public function view($formCode)
    {
        $form = $this->formModel->where('code', $formCode)->first();
        
        if (!$form) {
            return redirect()->to('/forms')->with('error', 'Form not found');
        }
        
        // Get panel fields using the panel_name from the form, or fallback to formCode
        $panelName = !empty($form['panel_name']) ? $form['panel_name'] : $formCode;
        $panelFields = $this->dbpanelModel->getPanelFields($panelName);
        
        if (empty($panelFields)) {
            return redirect()->to('/forms')->with('error', 'No fields configured for this form');
        }
        
        $data = [
            'title' => 'Form: ' . $form['description'],
            'form' => $form,
            'panel_name' => $panelName,
            'panel_fields' => $panelFields,
            'departments' => $this->departmentModel->findAll(),
            'priorities' => $this->priorityModel->getPriorityOptions() ?? [
                'low' => 'Low',
                'normal' => 'Normal', 
                'high' => 'High',
                'urgent' => 'Urgent',
                'critical' => 'Critical'
            ]
        ];
        
        return view('forms/view', $data);
    }
    
    public function submit()
    {
        $formId = $this->request->getPost('form_id');
        $panelName = $this->request->getPost('panel_name');
        $userType = session()->get('user_type');
        
        // Priority setting: Only service_staff and admin can set custom priority
        $requestedPriority = $this->request->getPost('priority') ?? 'normal';
        $canSetPriority = in_array($userType, ['service_staff', 'admin']);
        
        // If user cannot set priority, force it to 'normal'
        // This prevents requestors and other users from setting unauthorized priority levels
        $priority = $canSetPriority ? $requestedPriority : 'normal';
        
        // Validate that the priority exists in our system
        if ($canSetPriority && !empty($requestedPriority)) {
            try {
                $validPriority = $this->priorityModel->getPriorityByLevel($requestedPriority);
                if (!$validPriority) {
                    // If priority doesn't exist in database, fall back to 'normal'
                    $priority = 'normal';
                }
            } catch (\Exception $e) {
                // If there's any database error, fall back to 'normal'
                $priority = 'normal';
            }
        }
        
        // Handle reference file upload
        $referenceFile = $this->request->getFile('reference_file');
        $savedFileName = null;
        $originalFileName = null;
        
        if ($referenceFile && $referenceFile->isValid() && !$referenceFile->hasMoved()) {
            $originalFileName = $referenceFile->getClientName();
            $savedFileName = $referenceFile->getRandomName();
            
            // Create uploads directory if it doesn't exist
            $uploadPath = WRITEPATH . 'uploads/references/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $referenceFile->move($uploadPath, $savedFileName);
        }
        
        // Get all panel fields
        $panelFields = $this->dbpanelModel->getPanelFields($panelName);
        
        // Create a new submission record with priority and reference file
        $submissionData = [
            'form_id' => $formId,
            'panel_name' => $panelName,
            'submitted_by' => session()->get('user_id'),
            'status' => 'submitted',
            'priority' => $priority
        ];
        
        if ($savedFileName) {
            $submissionData['reference_file'] = $savedFileName;
            $submissionData['reference_file_original'] = $originalFileName;
        }
        
        // Debugging: log submission payload and POST data to trace unknown 'type' column errors
        try {
            log_message('debug', 'Forms::submit - submissionData: ' . json_encode($submissionData));
            log_message('debug', 'Forms::submit - POST data: ' . json_encode($this->request->getPost()));
        } catch (\Exception $e) {
            // If logging or json encoding fails, don't block submission; still attempt insert
            log_message('error', 'Forms::submit - debug log failed: ' . $e->getMessage());
        }

        $submissionId = $this->formSubmissionModel->insert($submissionData);
        
        // Save each field value based on user role
        foreach ($panelFields as $field) {
            $fieldName = $field['field_name'];
            $fieldRole = $field['field_role'] ?? 'both';
            
            // Only save fields that this user role should be able to edit
            $canEdit = false;
            if ($fieldRole === 'both') {
                $canEdit = true;
            } elseif ($fieldRole === 'requestor' && $userType === 'requestor') {
                $canEdit = true;
            } elseif ($fieldRole === 'service_staff' && $userType === 'service_staff') {
                $canEdit = true;
            }
            
            if ($canEdit) {
                // Accept single value or array (for checkbox groups)
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
        }
        
        return redirect()->to('/forms/my-submissions')
                        ->with('message', 'Form submitted successfully');
    }
    
    public function mySubmissions()
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        // Admin and superuser can see all submissions, others see only their own
        $filterUserId = in_array($userType, ['admin', 'superuser']) ? null : $userId;
        
        $title = in_array($userType, ['admin', 'superuser']) ? 'All Form Submissions' : 'My Form Submissions';
        
        $data = [
            'title' => $title,
            'submissions' => $this->formSubmissionModel->getSubmissionsWithDetails($filterUserId)
        ];
        
        return view('forms/my_submissions', $data);
    }
    
    public function pendingApproval()
    {
        try {
            // Only for approving_authority
            if (session()->get('user_type') !== 'approving_authority' && 
                session()->get('user_type') !== 'admin') {
                return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
            }
            
            // Get filter parameters
            $officeFilter = $this->request->getGet('office');
            $priorityFilter = $this->request->getGet('priority');
            
            // Get pending submissions with filters
            try {
                $submissions = $this->formSubmissionModel->getPendingApprovalsWithFilters($officeFilter, $priorityFilter);
            } catch (\Exception $e) {
                log_message('error', 'Error getting pending approvals: ' . $e->getMessage());
                $submissions = [];
            }
            
            // Get all office names for filter dropdown
            try {
                $officeNames = $this->db->table('offices')
                    ->select('DISTINCT description')
                    ->where('active', 1)
                    ->orderBy('description')
                    ->get()
                    ->getResultArray();
                $offices = array_column($officeNames, 'description');
            } catch (\Exception $e) {
                log_message('error', 'Error getting offices: ' . $e->getMessage());
                $offices = [];
            }
            
            // Get priority options with fallback
            try {
                $priorities = $this->priorityModel->getPriorityOptions();
            } catch (\Exception $e) {
                log_message('error', 'Error getting priorities: ' . $e->getMessage());
                $priorities = [
                    'low' => 'Low',
                    'normal' => 'Normal',
                    'high' => 'High',
                    'urgent' => 'Urgent',
                    'critical' => 'Critical'
                ];
            }
            
            $data = [
                'title' => 'Forms Pending Approval',
                'submissions' => $submissions ?? [],
                'offices' => $offices ?? [],
                'priorities' => $priorities ?? [],
                'selectedOffice' => $officeFilter ?? '',
                'selectedPriority' => $priorityFilter ?? ''
            ];
            
            return view('forms/pending_approval', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'Error in pendingApproval: ' . $e->getMessage());
            return redirect()->to('/dashboard')->with('error', 'An error occurred while loading pending approvals. Please try again.');
        }
    }
    
    public function pendingService()
    {
        $userId = session()->get('user_id');
        
        // Get submissions pending service
        $builder = $this->formSubmissionModel->builder();
        $builder->select('
            form_submissions.id, 
            form_submissions.form_id, 
            form_submissions.submitted_by, 
            form_submissions.status, 
            form_submissions.approved_at, 
            form_submissions.updated_at,
            forms.code as form_code, 
            forms.description as form_description,
            requestor.full_name as requestor_name,
            offices.description as office_name
        ');
        $builder->join('forms', 'forms.id = form_submissions.form_id');
        $builder->join('users as requestor', 'requestor.id = form_submissions.submitted_by');
        $builder->join('offices', 'offices.id = requestor.office_id', 'left');
        
    // Filter for forms assigned to this service staff.
    // Accept both 'approved' and 'pending_service' statuses for backward compatibility
    // (some flows set service_staff_id but leave status as 'approved')
    $builder->where('form_submissions.service_staff_id', $userId);
    $builder->whereIn('form_submissions.status', ['approved', 'pending_service']);
        
        $builder->orderBy('form_submissions.approved_at', 'DESC');
        
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
        
        // Build a query to get completed forms with all necessary details
        $builder = $this->db->table('form_submissions fs');
        
        // First, let's determine which completion date field exists
        $completionDateField = '';
        if ($this->db->fieldExists('completed_date', 'form_submissions')) {
            $completionDateField = 'fs.completed_date';
        } elseif ($this->db->fieldExists('completed_at', 'form_submissions')) {
            $completionDateField = 'fs.completed_at';
        } else {
            $completionDateField = 'fs.updated_at'; // Fallback
        }
        
        // Build the select statement with only existing columns
        $select = "
            fs.id, 
            fs.form_id, 
            fs.submitted_by, 
            fs.approver_id,
            fs.service_staff_id,
            fs.status, 
            fs.created_at,
            fs.updated_at,
            fs.approved_at,
            fs.service_staff_signature_date,
            {$completionDateField} as completion_date,
            f.code as form_code, 
            f.description as form_description,
            requestor.full_name as requestor_name,
            approver.full_name as approver_name,
            service_staff.full_name as service_staff_name,
            d.description as department_name
        ";
        
        $builder->select($select);
        $builder->join('forms f', 'f.id = fs.form_id', 'left');
        $builder->join('users requestor', 'requestor.id = fs.submitted_by', 'left');
        $builder->join('users approver', 'approver.id = fs.approver_id', 'left');
        $builder->join('users service_staff', 'service_staff.id = fs.service_staff_id', 'left');
        $builder->join('departments d', 'd.id = requestor.department_id', 'left');
        $builder->where('fs.status', 'completed');
        
        // For requestors, only show their own completed forms
        if ($userType === 'requestor') {
            $builder->where('fs.submitted_by', $userId);
        }
        
        $builder->orderBy('fs.id', 'DESC');
        
        $submissions = $builder->get()->getResultArray();
        
        $data = [
            'title' => 'Completed Forms',
            'submissions' => $submissions
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
        
        // Get submitter details
        $submitter = $this->userModel->find($submission['submitted_by']);
        
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
        
        // Get available service staff for assignment
        $availableServiceStaff = [];
        if (in_array($userType, ['admin', 'approving_authority'])) {
            $availableServiceStaff = $this->userModel->where('user_type', 'service_staff')
                                                    ->where('active', 1)
                                                    ->findAll();
        }
        
        // Determine if current user can take action on this form
        $canApprove = ($userType === 'approving_authority' && $submission['status'] === 'submitted');
        $canService = ($userType === 'service_staff' && $submission['status'] === 'pending_service' && $submission['service_staff_id'] == $userId);
        $canSignCompletion = ($userType === 'requestor' && $submission['submitted_by'] == $userId && 
                             !empty($submission['service_staff_signature_date']) && empty($submission['requestor_signature_date']));
        $canAssignServiceStaff = (in_array($userType, ['admin', 'approving_authority']) && 
                                 in_array($submission['status'], ['submitted', 'approved']) && 
                                 empty($submission['service_staff_id']));
        
        // Check if user has signature
        $currentUser = $this->userModel->find($userId);
        $hasSignature = !empty($currentUser['signature']);
        
        $data = [
            'title' => 'View Submission',
            'submission' => $submission,
            'form' => $form,
            'submitter' => $submitter,
            'panel_fields' => $panelFields,
            'submission_data' => $submissionData,
            'approver' => $approver,
            'service_staff' => $serviceStaff,
            'available_service_staff' => $availableServiceStaff,
            'canApprove' => $canApprove,
            'canService' => $canService,
            'canSignCompletion' => $canSignCompletion,
            'canAssignServiceStaff' => $canAssignServiceStaff,
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
            // Optional: assign service staff if provided on the approval form
            $serviceStaffId = $this->request->getPost('service_staff_id') ?? null;
            
            // Record approver signature and update status
            // Use model method to mark approved
            $this->formSubmissionModel->approveSubmission($id, $userId, $comments);

            // If a service staff was selected, persist it and move to pending_service
            if (!empty($serviceStaffId)) {
                try {
                    $this->formSubmissionModel->update($id, [
                        'service_staff_id' => $serviceStaffId,
                        'status' => 'pending_service'
                    ]);
                } catch (\Exception $e) {
                    log_message('error', 'Failed to assign service staff during signForm: ' . $e->getMessage());
                }
            }
            
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
    
    public function approveForm($submissionId)
    {
        try {
            $userId = session()->get('user_id');
            $userType = session()->get('user_type');
            
            if ($userType !== 'approving_authority' && $userType !== 'admin') {
                return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
            }
            
            // Get submission details
            $submission = $this->formSubmissionModel->find($submissionId);
            
            if (!$submission) {
                return redirect()->to('/forms/pending-approval')
                            ->with('error', 'Submission not found');
            }
            
            // Get form details
            $form = $this->formModel->find($submission['form_id']);
            if (!$form) {
                return redirect()->to('/forms/pending-approval')
                            ->with('error', 'Form not found');
            }
            
            // Get requestor details
            $requestor = $this->userModel->find($submission['submitted_by']);
            if (!$requestor) {
                return redirect()->to('/forms/pending-approval')
                            ->with('error', 'Requestor not found');
            }
            
            // Get submission data
            $submissionData = $this->formSubmissionDataModel->getSubmissionDataAsArray($submissionId);
            
            // Get panel fields
            $panelFields = $this->dbpanelModel->getPanelFields($submission['panel_name']);
            
            // Get available service staff - NEW CODE
            $userModel = new \App\Models\UserModel();
            $serviceStaff = $userModel->where('user_type', 'service_staff')
                                 ->where('active', 1)
                                 ->findAll();
            
            // Check if user has a signature
            $currentUser = $this->userModel->find($userId);
            $hasSignature = !empty($currentUser['signature']);
            
            $data = [
                'title' => 'Approve Form: ' . $form['code'],
                'submission' => $submission,
                'form' => $form,
                'requestor' => $requestor,
                'submission_data' => $submissionData ?? [],  // Safe fallback
                'panel_fields' => $panelFields ?? [],        // Safe fallback
                'serviceStaff' => $serviceStaff ?? [],
                'hasSignature' => $hasSignature,
                'current_user' => $currentUser
            ];
            
            return view('forms/approval_form', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'Error in approveForm: ' . $e->getMessage());
            return redirect()->to('/forms/pending-approval')
                        ->with('error', 'An error occurred while loading the approval form. Please try again.');
        }
    }

    /**
     * Show service form
     */
    public function serviceForm($id)
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        if ($userType !== 'service_staff' && $userType !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission) {
            return redirect()->to('/forms/pending-service')
                    ->with('error', 'Form not found');
        }
        
        // Check if this form is assigned to the current service staff
        if ($userType === 'service_staff' && $submission['service_staff_id'] != $userId) {
            return redirect()->to('/forms/pending-service')
                    ->with('error', 'This form is not assigned to you');
        }
        
        // Check if the form is in the correct status for servicing
        // Accept both 'approved' and 'pending_service' statuses for backward compatibility
        if (!in_array($submission['status'], ['approved', 'pending_service'])) {
            return redirect()->to('/forms/pending-service')
                    ->with('error', 'This form is not ready for service');
        }
        
        // Check if the form has already been serviced
        if (!empty($submission['service_staff_signature_date'])) {
            return redirect()->to('/forms/pending-service')
                    ->with('error', 'This form has already been serviced');
        }
        
        // Get form details
        $form = $this->formModel->find($submission['form_id']);
        
        // Get requestor details with office information
        $userModel = new \App\Models\UserModel();
        $requestorWithOffice = $userModel->getUsersWithOffice();
        $requestor = null;
        foreach ($requestorWithOffice as $user) {
            if ($user['id'] == $submission['submitted_by']) {
                $requestor = $user;
                break;
            }
        }
        
        // Fallback if office data not found
        if (!$requestor) {
            $requestor = $userModel->find($submission['submitted_by']);
        }
        
        // Get current user details - ADD THIS
        $currentUser = $userModel->find($userId);
        
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
            'submission_data' => $submissionData,
            'current_user' => $currentUser  // ADD THIS
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
        
        // Get submissions approved by the current user
        $builder = $this->formSubmissionModel->builder();
        $builder->select('
            form_submissions.id, 
            form_submissions.form_id, 
            form_submissions.submitted_by, 
            form_submissions.status, 
            form_submissions.approved_at, 
            form_submissions.updated_at,
            form_submissions.service_staff_id, 
            forms.code as form_code, 
            forms.description as form_description,
            requestor.full_name as requestor_name,
            service_staff.full_name as service_staff_name
        ');
        $builder->join('forms', 'forms.id = form_submissions.form_id');
        $builder->join('users as requestor', 'requestor.id = form_submissions.submitted_by');
        $builder->join('users as service_staff', 'service_staff.id = form_submissions.service_staff_id', 'left'); // Left join to include submissions without service staff
        $builder->where('form_submissions.approver_id', $userId);
        $builder->orderBy('form_submissions.approved_at', 'DESC');
        
        $submissions = $builder->get()->getResultArray();
        
        $data = [
            'title' => 'Forms Approved By Me',
            'submissions' => $submissions
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
        $action = $this->request->getPost('action');
        $comments = $this->request->getPost('comments');
        $serviceStaffId = $this->request->getPost('service_staff_id'); // NEW: Get selected service staff
        
        // Get the submission
        $submission = $this->formSubmissionModel->find($submissionId);
        
        if (!$submission) {
            return redirect()->to('/forms/pending-approval')
                        ->with('error', 'Submission not found');
        }
        
        // Update submission based on action
        if ($action === 'approve') {
            $updateData = [
                'status' => 'pending_service',
                'approver_id' => session()->get('user_id'),
                'approved_at' => date('Y-m-d H:i:s'),
                'approval_comments' => $comments,
                'service_staff_id' => $serviceStaffId, // NEW: Save selected service staff
            ];
            
            $this->formSubmissionModel->update($submissionId, $updateData);
            
            // Send notification to service staff - you can implement this
            
            return redirect()->to('/forms/approved-by-me')
                        ->with('message', 'Form has been approved and assigned to service staff.');
        } elseif ($action === 'reject') {
            // Existing rejection code...
        }
        
        return redirect()->to('/forms/pending-approval')
                    ->with('error', 'Invalid action');
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

    public function approveAll()
    {
        try {
            $userId = session()->get('user_id');
            $userType = session()->get('user_type');
            
            if (!in_array($userType, ['approving_authority', 'admin'])) {
                return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
            }
            
            $officeFilter = $this->request->getPost('office_filter');
            $priorityFilter = $this->request->getPost('priority_filter');
            
            // Use the same method as pendingApproval to get submissions
            $pendingSubmissions = $this->formSubmissionModel->getPendingApprovalsWithFilters($officeFilter, $priorityFilter);
            
            if (empty($pendingSubmissions)) {
                return redirect()->to('/forms/pending-approval')
                            ->with('error', 'No forms found matching the criteria');
            }
            
            $approvedCount = 0;
            $errors = [];
            
            foreach ($pendingSubmissions as $submission) {
                try {
                    $updateData = [
                        'status' => 'pending_service',
                        'approver_id' => $userId,
                        'approved_at' => date('Y-m-d H:i:s'),
                        'approval_comments' => 'Bulk approved'
                    ];
                    
                    $this->formSubmissionModel->update($submission['id'], $updateData);
                    $approvedCount++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Failed to approve submission ID {$submission['id']}: " . $e->getMessage();
                    log_message('error', "Bulk approval error for submission {$submission['id']}: " . $e->getMessage());
                }
            }
            
            $message = "Successfully approved {$approvedCount} forms";
            if (!empty($errors)) {
                $message .= ". " . count($errors) . " errors occurred - check logs for details.";
            }
            
            return redirect()->to('/forms/pending-approval')
                            ->with('message', $message);
                            
        } catch (\Exception $e) {
            log_message('error', 'Error in approveAll: ' . $e->getMessage());
            return redirect()->to('/forms/pending-approval')
                        ->with('error', 'An error occurred during bulk approval. Please try again.');
        }
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
        
        if (!$submission) {
            return redirect()->to('/forms/pending-service')
                        ->with('error', 'Form not found');
        }
        
        // Check if this form is assigned to the current service staff
        if ($userType === 'service_staff' && $submission['service_staff_id'] != $userId) {
            return redirect()->to('/forms/pending-service')
                        ->with('error', 'This form is not assigned to you');
        }
        
        // Check if the form is in the correct status for servicing
        if (!in_array($submission['status'], ['approved', 'pending_service'])) {
            return redirect()->to('/forms/pending-service')
                        ->with('error', 'This form is not ready for service');
        }
        
        // Check if the form has already been serviced
        if (!empty($submission['service_staff_signature_date'])) {
            return redirect()->to('/forms/pending-service')
                        ->with('error', 'This form has already been serviced');
        }
        
        // Get panel fields to know which ones the service staff can update
        $panelFields = $this->dbpanelModel->getPanelFields($submission['panel_name']);
        
        // Process and save each field value
        foreach ($panelFields as $field) {
            $fieldName = $field['field_name'];
            $fieldRole = $field['field_role'] ?? 'both';
            
            // Only process fields that service staff can edit
            if ($fieldRole === 'service_staff' || $fieldRole === 'both') {
                $fieldValue = $this->request->getPost($fieldName) ?? '';
                
                // Check if this field already has a value in the submission
                $existingData = $this->formSubmissionDataModel->where('submission_id', $submissionId)
                                                             ->where('field_name', $fieldName)
                                                             ->first();
                
                if ($existingData) {
                    // Update existing field value
                    $this->formSubmissionDataModel->update($existingData['id'], [
                        'field_value' => $fieldValue
                    ]);
                } else {
                    // Insert new field value
                    $this->formSubmissionDataModel->insert([
                        'submission_id' => $submissionId,
                        'field_name' => $fieldName,
                        'field_value' => $fieldValue
                    ]);
                }
            }
        }
        
        // Record service staff signature and mark as completed immediately
        $updateData = [
            'status' => 'completed', // Change from 'awaiting_requestor_signature' to 'completed'
            'service_staff_signature_date' => date('Y-m-d H:i:s'),
            'service_notes' => $notes,
            'requestor_signature_date' => date('Y-m-d H:i:s') // Add this to auto-complete the form
        ];
        
        // Add completed_date if the column exists
        if ($this->db->fieldExists('completed_date', 'form_submissions')) {
            $updateData['completed_date'] = date('Y-m-d H:i:s');
        } elseif ($this->db->fieldExists('completed_at', 'form_submissions')) {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }
        
        $this->formSubmissionModel->update($submissionId, $updateData);
        
        return redirect()->to('/forms/serviced-by-me')
                    ->with('message', 'Service completed and form signed successfully. The form has been marked as completed.');
    }    

    public function export($id, $format = 'pdf')
    {
        // Ensure submission exists and is completed before allowing export
        $submission = $this->formSubmissionModel->find($id);
        if (!$submission) {
            return redirect()->to('/forms/my-submissions')->with('error', 'Submission not found');
        }

        if (($submission['status'] ?? '') !== 'completed') {
            return redirect()->to('/forms/my-submissions')->with('error', 'Export is only available for completed submissions');
        }

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
    
    /**
     * Assign service staff to a submission
     */
    public function assignServiceStaff()
    {
        try {
            $userId = session()->get('user_id');
            $userType = session()->get('user_type');
            
            // Only admins and approving authorities can assign service staff
            if (!in_array($userType, ['admin', 'approving_authority'])) {
                return redirect()->back()->with('error', 'Unauthorized access');
            }
            
            $submissionId = $this->request->getPost('submission_id');
            $serviceStaffId = $this->request->getPost('service_staff_id');
            
            if (empty($submissionId) || empty($serviceStaffId)) {
                return redirect()->back()->with('error', 'Missing required fields');
            }
            
            // Get the submission
            $submission = $this->formSubmissionModel->find($submissionId);
            if (!$submission) {
                return redirect()->back()->with('error', 'Submission not found');
            }
            
            // Verify the service staff exists and is active
            $serviceStaff = $this->userModel->where('id', $serviceStaffId)
                                          ->where('user_type', 'service_staff')
                                          ->where('active', 1)
                                          ->first();
            
            if (!$serviceStaff) {
                return redirect()->back()->with('error', 'Invalid service staff selected');
            }
            
            // Update the submission with service staff assignment
            $updateData = [
                'service_staff_id' => $serviceStaffId,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // If submission is not yet approved, also update status
            if ($submission['status'] === 'submitted') {
                $updateData['status'] = 'pending_service';
                $updateData['approver_id'] = $userId;
                $updateData['approved_at'] = date('Y-m-d H:i:s');
                $updateData['approval_comments'] = 'Auto-approved with service staff assignment';
            }
            
            $this->formSubmissionModel->update($submissionId, $updateData);
            
            log_message('info', "Service staff {$serviceStaff['full_name']} assigned to submission {$submissionId} by user {$userId}");
            
            return redirect()->back()->with('message', 'Service staff assigned successfully');
            
        } catch (\Exception $e) {
            log_message('error', 'Error in assignServiceStaff: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while assigning service staff');
        }
    }


}

