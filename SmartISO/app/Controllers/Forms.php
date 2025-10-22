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
    $req = $this->getRequest();
    // Read raw GET values then normalize to integers where appropriate so
    // the query builder receives consistent types (helps avoid "0"/"" problems)
    $rawDept = $req->getGet('department');
    $rawOffice = $req->getGet('office');
    $selectedDepartment = (is_numeric($rawDept) && $rawDept !== '') ? (int)$rawDept : null;
    $selectedOffice = (is_numeric($rawOffice) && $rawOffice !== '') ? (int)$rawOffice : null;

        // For non-admin users, restrict to their department only
        $userDepartmentId = session()->get('department_id');
        $userType = session()->get('user_type');
        $isAdmin = in_array($userType, ['admin', 'superuser', 'department_admin']);
        
        // If user is not an admin and has a department, force filter to their department
        if (!$isAdmin && $userDepartmentId) {
            $selectedDepartment = (int)$userDepartmentId;
        }

        $departments = $this->departmentModel->findAll();
        // Offices: load all (including inactive) so user can still filter; then optionally restrict in list
        try {
            $allOffices = $this->officeModel->orderBy('description','ASC')->findAll();
        } catch (\Throwable $e) {
            $allOffices = [];
        }

        // If both department and office were provided, ensure the selected office actually
        // belongs to the selected department. If it doesn't, clear the selected office so
        // we don't accidentally show forms for an unrelated office.
        if (!empty($selectedDepartment) && !empty($selectedOffice)) {
            $officeOk = false;
            // Quick check against offices.department_id
            foreach ($allOffices as $o) {
                if ((int)($o['id'] ?? 0) === $selectedOffice) {
                    if (!empty($o['department_id']) && (int)$o['department_id'] === $selectedDepartment) {
                        $officeOk = true;
                    }
                    break;
                }
            }
            // If still not matched, consult legacy pivot table department_office
            if (!$officeOk) {
                $db = \Config\Database::connect();
                if ($db->tableExists('department_office')) {
                    $row = $db->table('department_office')
                              ->select('1')
                              ->where('department_id', $selectedDepartment)
                              ->where('office_id', $selectedOffice)
                              ->get()
                              ->getRowArray();
                    if (!empty($row)) { $officeOk = true; }
                }
            }
            if (!$officeOk) {
                // clear the selected office to avoid showing unrelated forms
                $selectedOffice = null;
            }
        }
        // Keep both the full office list and a department-filtered list. The view
        // prefers the full list and lets JS hide/show options, but we keep the
        // filtered list for backward compatibility.
        $allOffices = is_array($allOffices) ? $allOffices : [];
        $offices = [];
        if (!empty($selectedDepartment)) {
            foreach ($allOffices as $o) {
                if ((string)($o['department_id'] ?? '') === (string)$selectedDepartment) {
                    $offices[] = $o;
                }
            }
        } else {
            $offices = $allOffices;
        }
        // Guarantee office array shape
        if (!is_array($offices)) { $offices = []; }

        $forms = [];
        if (isset($this->formModel)) {
            // Base query: join office and departments. A form may have a department_id
            // or rely on the office -> department relationship. Join departments
            // from both the form (d1) and the office (d2) and use COALESCE so
            // we display a department name when available via either path.
            // Use form.department_id OR office.department_id (no pivot) to determine a form's department
            $builder = $this->db->table('forms f')
                ->select('f.*, COALESCE(d1.description, d2.description) AS department_name, o.description AS office_name')
                ->join('departments d1', 'd1.id = f.department_id', 'left')
                ->join('offices o', 'o.id = f.office_id', 'left')
                ->join('departments d2', 'd2.id = o.department_id', 'left');
            if (!empty($selectedDepartment) && !empty($selectedOffice)) {
                // Both department and office selected: intersection semantics.
                // Ensure the form belongs to the selected office AND that the
                // department matches either the form.department_id or the office's department_id.
                $builder->groupStart()
                        ->where('f.office_id', $selectedOffice)
                        ->groupEnd()
                    ->groupStart()
                        ->groupStart()
                            ->where('f.department_id', $selectedDepartment)
                            ->orWhere('o.department_id', $selectedDepartment)
                        ->groupEnd()
                    ->groupEnd();
            } else {
                if (!empty($selectedDepartment)) {
                    // Match forms where department is set on the form OR inherited via the office.department_id
                    $builder->groupStart()
                            ->where('f.department_id', $selectedDepartment)
                            ->orWhere('o.department_id', $selectedDepartment)
                        ->groupEnd();
                }
                if (!empty($selectedOffice)) { $builder->where('f.office_id', $selectedOffice); }
            }
            // Capture compiled SQL for quick debugging in the view
            try {
                $compiled = $builder->getCompiledSelect();
            } catch (\Throwable $e) {
                $compiled = '';
            }
            $forms = $builder->orderBy('f.description','ASC')->get()->getResultArray();
        }

        $data = [
            'title' => 'Available Forms',
            'forms' => $forms,
            'departments' => $departments,
            // pass both full list and filtered list
            'offices' => $offices,
            'allOffices' => $allOffices,
            'selectedDepartment' => $selectedDepartment,
            'selectedOffice' => $selectedOffice
        ];

        // include compiled SQL for debugging (if available)
        if (!empty($compiled)) { $data['debugSql'] = $compiled; }

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
        $requestedPriority = $this->request->getPost('priority') ?? 'low';
        $canSetPriority = in_array($userType, ['service_staff', 'admin']);
        
        // If user cannot set priority, force it to 'low'
        // This prevents requestors and other users from setting unauthorized priority levels
        $priority = $canSetPriority ? $requestedPriority : 'low';
        
        // Validate that the priority exists in our system
        if ($canSetPriority && !empty($requestedPriority)) {
            try {
                $validPriority = $this->priorityModel->getPriorityByLevel($requestedPriority);
                if (!$validPriority) {
                    // If priority doesn't exist in database, fall back to 'low'
                    $priority = 'low';
                }
            } catch (\Exception $e) {
                // If there's any database error, fall back to 'low'
                $priority = 'low';
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

        // Optional: Auto-create pending schedule when submissions are created
        try {
            // Prefer runtime DB configuration; fall back to App config
            $configModel = new \App\Models\ConfigurationModel();
            $dbFlag = $configModel->getConfig('auto_create_schedule_on_submit', null);
            $appConf = config('App');
            $enabled = ($dbFlag === null) ? ($appConf->autoCreateScheduleOnSubmit ?? false) : (bool)$dbFlag;

            if (!empty($enabled) && class_exists('App\\Models\\ScheduleModel')) {
                $scheduleModel = new \App\Models\ScheduleModel();
                if (property_exists($scheduleModel, 'allowedFields') && in_array('submission_id', $scheduleModel->allowedFields)) {
                    $scheduledDate = date('Y-m-d');
                    $schedData = [
                        'submission_id' => $submissionId,
                        'scheduled_date' => $scheduledDate,
                        'scheduled_time' => '09:00:00',
                        'duration_minutes' => 60,
                        'assigned_staff_id' => null,
                        'location' => '',
                        'notes' => 'Auto-created schedule on submit',
                        'status' => 'pending'
                    ];
                    // Compute ETA based on priority with new mapping:
                    // low = 7 calendar days, normal = 5 business days, high = 3 business days
                    // medium = 5 business days, urgent = 2 business days, critical = 1 business day
                    $etaDays = null; $estimatedDate = null;
                    if ($priority === 'low') {
                        $etaDays = 7;
                        $estimatedDate = date('Y-m-d', strtotime($scheduledDate . ' +7 days'));
                    } elseif ($priority === 'normal' || $priority === 'medium') {
                        $etaDays = 5;
                        // Use Schedule controller helper if available
                        try {
                            $schCtrl = new \App\Controllers\Schedule();
                            $estimatedDate = $schCtrl->addBusinessDays($scheduledDate, 5);
                        } catch (\Throwable $e) {
                            // fallback: add calendar days (best-effort)
                            $estimatedDate = date('Y-m-d', strtotime($scheduledDate . ' +5 days'));
                        }
                    } elseif ($priority === 'high') {
                        $etaDays = 3;
                        try {
                            $schCtrl = new \App\Controllers\Schedule();
                            $estimatedDate = $schCtrl->addBusinessDays($scheduledDate, 3);
                        } catch (\Throwable $e) {
                            $estimatedDate = date('Y-m-d', strtotime($scheduledDate . ' +3 days'));
                        }
                    } elseif ($priority === 'urgent') {
                        $etaDays = 2;
                        try {
                            $schCtrl = new \App\Controllers\Schedule();
                            $estimatedDate = $schCtrl->addBusinessDays($scheduledDate, 2);
                        } catch (\Throwable $e) {
                            $estimatedDate = date('Y-m-d', strtotime($scheduledDate . ' +2 days'));
                        }
                    } elseif ($priority === 'critical') {
                        $etaDays = 1;
                        $estimatedDate = date('Y-m-d', strtotime($scheduledDate . ' +1 day'));
                    }
                    if ($etaDays && $estimatedDate) {
                        $schedData['eta_days'] = $etaDays;
                        $schedData['estimated_date'] = $estimatedDate;
                        $schedData['priority_level'] = $priority;
                    }
                    try {
                        $scheduleModel->insert($schedData);
                    } catch (\Throwable $e) {
                        log_message('error', 'Auto-schedule on submit failed for submission ' . $submissionId . ': ' . $e->getMessage());
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error while attempting auto-create schedule on submit: ' . $e->getMessage());
        }
        
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
        $userDepartmentId = session()->get('department_id');
        
        // Admin and superuser can see all submissions
        // Department admins see only their department's submissions
        // Regular users see only their own submissions
        $isGlobalAdmin = in_array($userType, ['admin', 'superuser']);
        $isDepartmentAdmin = session()->get('is_department_admin') && session()->get('scoped_department_id');
        
        if ($isGlobalAdmin) {
            // Global admins see everything
            $filterUserId = null;
            $filterDepartmentId = null;
            $title = 'All Form Submissions';
        } elseif ($isDepartmentAdmin) {
            // Department admins see all submissions from their department
            $filterUserId = null;
            $filterDepartmentId = session()->get('scoped_department_id');
            $title = 'Department Form Submissions';
        } else {
            // Regular users see only their own submissions
            $filterUserId = $userId;
            $filterDepartmentId = null;
            $title = 'My Form Submissions';
        }
        
        // Get submissions with optional filters
        $builder = $this->formSubmissionModel->builder();
        $builder->select('form_submissions.*, forms.code as form_code, forms.description as form_description, 
                         users.full_name as requestor_name, departments.description as department_name')
                ->join('forms', 'forms.id = form_submissions.form_id')
                ->join('users', 'users.id = form_submissions.submitted_by')
                ->join('departments', 'departments.id = users.department_id', 'left');
        
        if ($filterUserId) {
            $builder->where('form_submissions.submitted_by', $filterUserId);
        }
        
        if ($filterDepartmentId) {
            $builder->where('users.department_id', $filterDepartmentId);
        }
        
        $builder->orderBy('form_submissions.updated_at', 'DESC');
        $submissions = $builder->get()->getResultArray();
        
        $data = [
            'title' => $title,
            'submissions' => $submissions,
            'isDepartmentFiltered' => !$isGlobalAdmin
        ];
        
        return view('forms/my_submissions', $data);
    }
    
    public function pendingApproval()
    {
        try {
            // Only for approving_authority and admins
            if (session()->get('user_type') !== 'approving_authority' && 
                session()->get('user_type') !== 'admin' &&
                session()->get('user_type') !== 'department_admin') {
                return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
            }
            
            $userType = session()->get('user_type');
            $userDepartmentId = session()->get('department_id');
            $isGlobalAdmin = in_array($userType, ['admin', 'superuser']);
            $isDepartmentAdmin = session()->get('is_department_admin') && session()->get('scoped_department_id');
            
            // Get filter parameters
            $departmentFilter = $this->request->getGet('department');
            $priorityFilter = $this->request->getGet('priority');
            
            // For non-admin users, restrict to their department
            if (!$isGlobalAdmin && $userDepartmentId && !$departmentFilter) {
                $departmentFilter = $userDepartmentId;
            }
            if ($isDepartmentAdmin && !$departmentFilter) {
                $departmentFilter = session()->get('scoped_department_id');
            }
            
            // Get pending submissions with department filtering
            try {
                $builder = $this->formSubmissionModel->builder();
                $builder->select('form_submissions.*, forms.code as form_code, forms.description as form_description,
                                 users.full_name as requestor_name, users.department_id,
                                 departments.description as department_name')
                        ->join('forms', 'forms.id = form_submissions.form_id')
                        ->join('users', 'users.id = form_submissions.submitted_by')
                        ->join('departments', 'departments.id = users.department_id', 'left')
                        ->where('form_submissions.status', 'submitted');
                
                // Apply department filter
                if ($departmentFilter) {
                    $builder->where('users.department_id', $departmentFilter);
                }
                
                // Apply priority filter if provided
                if ($priorityFilter) {
                    $builder->where('form_submissions.priority', $priorityFilter);
                }
                
                $builder->orderBy('form_submissions.priority', 'DESC')
                        ->orderBy('form_submissions.updated_at', 'ASC');
                
                $submissions = $builder->get()->getResultArray();
            } catch (\Exception $e) {
                log_message('error', 'Error getting pending approvals: ' . $e->getMessage());
                $submissions = [];
            }
            
            // Get departments for filter dropdown (all for admins, only user's for others)
            try {
                $deptBuilder = $this->db->table('departments')
                    ->select('id, description')
                    ->where('active', 1)
                    ->orderBy('description');
                
                if (!$isGlobalAdmin && $userDepartmentId) {
                    $deptBuilder->where('id', $userDepartmentId);
                }
                if ($isDepartmentAdmin) {
                    $deptBuilder->where('id', session()->get('scoped_department_id'));
                }
                
                $departments = $deptBuilder->get()->getResultArray();
            } catch (\Exception $e) {
                log_message('error', 'Error getting departments: ' . $e->getMessage());
                $departments = [];
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
                'departments' => $departments ?? [],
                'priorities' => $priorities ?? [],
                'selectedDepartment' => $departmentFilter ?? '',
                'selectedPriority' => $priorityFilter ?? '',
                'isDepartmentFiltered' => !$isGlobalAdmin
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
        $userType = session()->get('user_type');
        $userDepartmentId = session()->get('department_id');
        $isGlobalAdmin = in_array($userType, ['admin', 'superuser']);
        $isDepartmentAdmin = session()->get('is_department_admin') && session()->get('scoped_department_id');
        
        // Get submissions pending service
        $builder = $this->formSubmissionModel->builder();
        $builder->select('
            form_submissions.id, 
            form_submissions.form_id, 
            form_submissions.submitted_by, 
            form_submissions.status, 
            form_submissions.priority,
            form_submissions.approved_at, 
            form_submissions.updated_at,
            forms.code as form_code, 
            forms.description as form_description,
            requestor.full_name as requestor_name,
            requestor.department_id,
            d.description as department_name,
            sch.priority_level, 
            sch.eta_days, 
            sch.estimated_date
        ');
        $builder->join('forms', 'forms.id = form_submissions.form_id');
        $builder->join('users as requestor', 'requestor.id = form_submissions.submitted_by');
        $builder->join('departments d', 'd.id = requestor.department_id', 'left');
        $builder->join('schedules sch', 'sch.submission_id = form_submissions.id', 'left');
        
        // Filter for forms assigned to this service staff.
        // Accept both 'approved' and 'pending_service' statuses for backward compatibility
        // (some flows set service_staff_id but leave status as 'approved')
        $builder->where('form_submissions.service_staff_id', $userId);
        $builder->whereIn('form_submissions.status', ['approved', 'pending_service']);
        
        // Add department filtering for non-admin users
        if (!$isGlobalAdmin && $userDepartmentId) {
            $builder->where('requestor.department_id', $userDepartmentId);
        }
        if ($isDepartmentAdmin) {
            $builder->where('requestor.department_id', session()->get('scoped_department_id'));
        }
        
        $builder->orderBy('form_submissions.approved_at', 'DESC');
        
        $submissions = $builder->get()->getResultArray();
        
        $data = [
            'title' => 'Forms Pending Service',
            'submissions' => $submissions,
            'isDepartmentFiltered' => !$isGlobalAdmin
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
            fs.priority,
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
            d.description as department_name,
            sch.priority_level, 
            sch.eta_days, 
            sch.estimated_date
        ";
        
        $builder->select($select);
        $builder->join('forms f', 'f.id = fs.form_id', 'left');
        $builder->join('users requestor', 'requestor.id = fs.submitted_by', 'left');
        $builder->join('users approver', 'approver.id = fs.approver_id', 'left');
        $builder->join('users service_staff', 'service_staff.id = fs.service_staff_id', 'left');
        $builder->join('departments d', 'd.id = requestor.department_id', 'left');
        $builder->join('schedules sch', 'sch.submission_id = fs.id', 'left');
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
        
        // Department verification for non-admin users
        $userDepartmentId = session()->get('department_id');
        $isAdmin = in_array($userType, ['admin', 'superuser', 'department_admin']);

        if (!$isAdmin && $userDepartmentId) {
            // Get submitter's department
            $submitter = $this->userModel->find($submission['submitted_by']);
            if (!$submitter || $submitter['department_id'] != $userDepartmentId) {
                return redirect()->to('/dashboard')
                    ->with('error', 'You can only view submissions from your department');
            }
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
        // Allow service staff to service when status is either 'pending_service' or legacy 'approved'
        $canService = (
            $userType === 'service_staff'
            && in_array($submission['status'], ['approved', 'pending_service'])
            && $submission['service_staff_id'] == $userId
        );
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

            // NEW: Enforce that a service staff member must be selected before approving
            if (empty($serviceStaffId)) {
                return redirect()->back()->with('error', 'Please select a service staff member before approving.');
            }
            
            // Record approver signature and update status
            // Use model method to mark approved
            $this->formSubmissionModel->approveSubmission($id, $userId, $comments);

            // Persist service staff assignment and move status to pending_service
            try {
                $this->formSubmissionModel->update($id, [
                    'status' => 'pending_service'
                ]);
                $this->formSubmissionModel->assignServiceStaff($id, $serviceStaffId);
            } catch (\Exception $e) {
                log_message('error', 'Failed to assign service staff during signForm: ' . $e->getMessage());
            }

            // OPTIONAL: Auto-create a pending schedule when a submission is approved and assigned
            // This ensures consistency between all approval flows (signForm, submitApproval, and assignServiceStaff)
            // Auto-create schedule only if enabled in config
            try {
                $appConf = config('App');
                if (!empty($appConf->autoCreateScheduleOnApproval) && class_exists('App\\Models\\ScheduleModel')) {
                    $scheduleModel = new \App\Models\ScheduleModel();
                    // Only insert if ScheduleModel allows submission_id
                    if (property_exists($scheduleModel, 'allowedFields') && in_array('submission_id', $scheduleModel->allowedFields)) {
                        // Check if a schedule already exists for this submission to avoid duplicates
                        $existingSchedule = $scheduleModel->where('submission_id', $id)->first();
                        if (!$existingSchedule) {
                            $schedData = [
                                'submission_id' => $id,
                                'scheduled_date' => date('Y-m-d'),
                                'scheduled_time' => '09:00:00',
                                'duration_minutes' => 60,
                                'assigned_staff_id' => $serviceStaffId,
                                'location' => '',
                                'notes' => 'Auto-created schedule on approval via signForm',
                                'status' => 'pending'
                            ];

                            // Insert quietly; if it fails, log but don't block approval flow
                            try {
                                $scheduleModel->insert($schedData);
                                log_message('info', "Auto-created schedule for submission {$id} via signForm with service staff {$serviceStaffId}");
                            } catch (\Throwable $inner) {
                                log_message('error', 'Auto-schedule creation failed for submission ' . $id . ': ' . $inner->getMessage());
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Non-fatal: log and continue
                log_message('error', 'Error while attempting to auto-create schedule in signForm: ' . $e->getMessage());
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
            
            // Department verification for non-admin approvers
            $userDepartmentId = session()->get('department_id');
            $isAdmin = in_array($userType, ['admin', 'superuser']);

            if (!$isAdmin && $userDepartmentId) {
                $requestor = $this->userModel->find($submission['submitted_by']);
                if (!$requestor || $requestor['department_id'] != $userDepartmentId) {
                    return redirect()->to('/forms/pending-approval')
                        ->with('error', 'You can only approve submissions from your department');
                }
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
            
            // Get available service staff - filtered by department for non-admins
            $userModel = new \App\Models\UserModel();
            $userDepartmentId = session()->get('department_id');
            $isAdmin = in_array(session()->get('user_type'), ['admin', 'superuser']);

            if ($isAdmin) {
                // Admins can assign any service staff
                $serviceStaff = $userModel->where('user_type', 'service_staff')
                                          ->where('active', 1)
                                          ->findAll();
            } else if ($userDepartmentId) {
                // Non-admins can only assign service staff from their department
                $serviceStaff = $userModel->where('user_type', 'service_staff')
                                          ->where('active', 1)
                                          ->where('department_id', $userDepartmentId)
                                          ->findAll();
            } else {
                $serviceStaff = [];
            }
            
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
        
        // Department verification for non-admin service staff
        $userDepartmentId = session()->get('department_id');
        $isAdmin = in_array($userType, ['admin', 'superuser']);

        if (!$isAdmin && $userDepartmentId) {
            $requestor = $this->userModel->find($submission['submitted_by']);
            if (!$requestor || $requestor['department_id'] != $userDepartmentId) {
                return redirect()->to('/forms/pending-service')
                    ->with('error', 'You can only service submissions from your department');
            }
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

        // Strengthened validation: enforce file, size, mime
        $validationRules = [
            'signature' => [
                'label' => 'Signature',
                'rules' => 'uploaded[signature]|max_size[signature,512]|mime_in[signature,image/png,image/jpeg]' // 512KB limit tightened
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

        // Additional content inspection (signature spoof + simple malware mitigation)
        $tmpPath = $file->getTempName();
        $imageInfo = @getimagesize($tmpPath);
        if (!$imageInfo || !in_array($imageInfo[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG])) {
            return redirect()->back()->with('error', 'Corrupted or unsupported image content');
        }

        // Re-encode image to strip any embedded payloads
        $isPng = ($imageInfo[2] === IMAGETYPE_PNG);
        $imageResource = $isPng ? imagecreatefrompng($tmpPath) : imagecreatefromjpeg($tmpPath);
        if (!$imageResource) {
            return redirect()->back()->with('error', 'Failed to process image');
        }

        // Generate deterministic sanitized filename
        $newName = $userId . '_' . time() . ($isPng ? '.png' : '.jpg');
        $targetDir = ROOTPATH . 'public/uploads/signatures/';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }
        $targetPath = $targetDir . $newName;

        $writeOk = $isPng ? imagepng($imageResource, $targetPath) : imagejpeg($imageResource, $targetPath, 90);
        imagedestroy($imageResource);

        if (!$writeOk || !file_exists($targetPath)) {
            return redirect()->back()->with('error', 'Failed to store sanitized image');
        }

        // Persist filename in DB (avoid using original upload directly)
        try {
            $this->userModel->update($userId, ['signature' => $newName]);
            // Optionally remove original tmp moved file (CI might auto-clean). We never move() original to avoid trusting it.
            return redirect()->to('/profile')
                ->with('message', 'Signature uploaded securely');
        } catch (\Exception $e) {
            @unlink($targetPath);
            return redirect()->back()->with('error', 'Error saving signature: ' . $e->getMessage());
        }
    }

    public function servicedByMe()
    {
        $userId = session()->get('user_id');
        
        // Ensure user is service staff
        if (session()->get('user_type') !== 'service_staff') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }
        
        // Get forms serviced by this user with query builder
        $builder = $this->formSubmissionModel->builder();
        $builder->select('
            form_submissions.id, 
            form_submissions.form_id, 
            form_submissions.submitted_by, 
            form_submissions.status, 
            form_submissions.priority,
            form_submissions.created_at, 
            form_submissions.updated_at,
            form_submissions.approved_at,
            form_submissions.service_staff_signature_date,
            forms.code as form_code, 
            forms.description as form_description,
            requestor.full_name as requestor_name,
            sch.priority_level, 
            sch.eta_days, 
            sch.estimated_date
        ');
        $builder->join('forms', 'forms.id = form_submissions.form_id');
        $builder->join('users as requestor', 'requestor.id = form_submissions.submitted_by');
        $builder->join('schedules sch', 'sch.submission_id = form_submissions.id', 'left');
        $builder->where('form_submissions.service_staff_id', $userId);
        
        // Department filtering for non-admin service staff
        $userDepartmentId = session()->get('department_id');
        $userType = session()->get('user_type');
        $isAdmin = in_array($userType, ['admin', 'superuser', 'department_admin']);

        if (!$isAdmin && $userDepartmentId) {
            $builder->where('requestor.department_id', $userDepartmentId);
        }
        
        $builder->orderBy('form_submissions.updated_at', 'DESC');
        
        $submissions = $builder->get()->getResultArray();
        
        $data = [
            'title' => 'Forms Serviced By Me',
            'submissions' => $submissions
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
            form_submissions.priority,
            form_submissions.approved_at, 
            form_submissions.updated_at,
            form_submissions.service_staff_id, 
            forms.code as form_code, 
            forms.description as form_description,
            requestor.full_name as requestor_name,
            service_staff.full_name as service_staff_name,
            sch.priority_level, 
            sch.eta_days, 
            sch.estimated_date
        ');
        $builder->join('forms', 'forms.id = form_submissions.form_id');
        $builder->join('users as requestor', 'requestor.id = form_submissions.submitted_by');
        $builder->join('users as service_staff', 'service_staff.id = form_submissions.service_staff_id', 'left'); // Left join to include submissions without service staff
        $builder->join('schedules sch', 'sch.submission_id = form_submissions.id', 'left');
        $builder->where('form_submissions.approver_id', $userId);
        
        // Department filtering for non-admin approvers
        $userDepartmentId = session()->get('department_id');
        $userType = session()->get('user_type');
        $isAdmin = in_array($userType, ['admin', 'superuser', 'department_admin']);

        if (!$isAdmin && $userDepartmentId) {
            $builder->where('requestor.department_id', $userDepartmentId);
        }
        
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
        
        // Get forms rejected by this user with query builder for department filtering
        $builder = $this->formSubmissionModel->builder();
        $builder->select('
            form_submissions.*,
            forms.code as form_code,
            forms.description as form_description,
            requestor.full_name as requestor_name
        ');
        $builder->join('forms', 'forms.id = form_submissions.form_id');
        $builder->join('users as requestor', 'requestor.id = form_submissions.submitted_by');
        $builder->where('form_submissions.approver_id', $userId);
        $builder->where('form_submissions.status', 'rejected');
        
        // Department filtering for non-admin approvers
        $userDepartmentId = session()->get('department_id');
        $isAdmin = in_array($userType, ['admin', 'superuser', 'department_admin']);

        if (!$isAdmin && $userDepartmentId) {
            $builder->where('requestor.department_id', $userDepartmentId);
        }
        
        $builder->orderBy('form_submissions.updated_at', 'DESC');
        
        $submissionsWithDetails = $builder->get()->getResultArray();
        
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
            // Require service staff selection when approving
            if (empty($serviceStaffId)) {
                return redirect()->back()->with('error', 'Please select a service staff member before approving.');
            }
            $updateData = [
                'status' => 'pending_service',
                'approver_id' => session()->get('user_id'),
                'approved_at' => date('Y-m-d H:i:s'),
                'approver_signature_date' => date('Y-m-d H:i:s'),
                'approval_comments' => $comments,
                'service_staff_id' => $serviceStaffId, // NEW: Save selected service staff
            ];
            
            $this->formSubmissionModel->update($submissionId, $updateData);

            // Send notification to service staff via model helper so it is recorded
            if (!empty($serviceStaffId)) {
                try {
                    $this->formSubmissionModel->assignServiceStaff($submissionId, $serviceStaffId);
                } catch (\Exception $e) {
                    log_message('error', 'Failed to send service assignment notification in submitApproval: ' . $e->getMessage());
                }
            }

            // OPTIONAL: Auto-create a pending schedule when a submission is approved and assigned
            // Assumption: approval + service staff assignment implies a service should be scheduled.
            // We create a minimal 'pending' schedule entry (non-blocking) so it appears on the calendar.
            // Auto-create schedule only if enabled in config
            try {
                $appConf = config('App');
                if (!empty($appConf->autoCreateScheduleOnApproval) && class_exists('App\\Models\\ScheduleModel')) {
                    $scheduleModel = new \App\Models\ScheduleModel();
                    // Only insert if ScheduleModel allows submission_id
                    if (property_exists($scheduleModel, 'allowedFields') && in_array('submission_id', $scheduleModel->allowedFields)) {
                        // Check if a schedule already exists for this submission to avoid duplicates
                        $existingSchedule = $scheduleModel->where('submission_id', $submissionId)->first();
                        if (!$existingSchedule) {
                            $schedData = [
                                'submission_id' => $submissionId,
                                'scheduled_date' => date('Y-m-d'),
                                'scheduled_time' => '09:00:00',
                                'duration_minutes' => 60,
                                'assigned_staff_id' => $serviceStaffId,
                                'location' => '',
                                'notes' => 'Auto-created schedule on approval',
                                'status' => 'pending'
                            ];

                            // Insert quietly; if it fails, log but don't block approval flow
                            try {
                                $scheduleModel->insert($schedData);
                                log_message('info', "Auto-created schedule for submission {$submissionId} on approval with service staff {$serviceStaffId}");
                            } catch (\Throwable $inner) {
                                log_message('error', 'Auto-schedule creation failed for submission ' . $submissionId . ': ' . $inner->getMessage());
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Non-fatal: log and continue
                log_message('error', 'Error while attempting to auto-create schedule in submitApproval: ' . $e->getMessage());
            }
            
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
            
            $departmentFilter = $this->request->getPost('department_filter');
            $priorityFilter = $this->request->getPost('priority_filter');
            
            // Use the same method as pendingApproval to get submissions
            $pendingSubmissions = $this->formSubmissionModel->getPendingApprovalsWithFilters($departmentFilter, $priorityFilter);
            
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
                        'approver_signature_date' => date('Y-m-d H:i:s'),
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
        // Get user context
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        $userDepartmentId = session()->get('department_id');
        $isAdmin = in_array($userType, ['admin', 'superuser', 'department_admin']);
        
        // Ensure submission exists
        $submission = $this->formSubmissionModel->find($id);
        if (!$submission) {
            return redirect()->to('/forms/my-submissions')->with('error', 'Submission not found');
        }
        
        // Department verification for non-admins
        if (!$isAdmin && $userDepartmentId) {
            $submitter = $this->userModel->find($submission['submitted_by']);
            if (!$submitter || $submitter['department_id'] != $userDepartmentId) {
                return redirect()->to('/dashboard')
                    ->with('error', 'You can only export submissions from your department');
            }
        }
        
        // Ensure submission is completed before allowing export
        if (($submission['status'] ?? '') !== 'completed') {
            return redirect()->to('/forms/my-submissions')->with('error', 'Export is only available for completed submissions');
        }

        $format = strtolower($format);

        // PdfGenerator::generateFormPdf() handles both PDF and Word formats
        // It will convert DOCX to PDF using iLovePDF when format=pdf
        if (in_array($format, ['pdf','word','docx'])) {
            return redirect()->to('/pdfgenerator/generateFormPdf/' . $id . '/' . $format);
        }

        return redirect()->back()->with('error', 'Invalid export format');
    }

    /**
     * Handle DOCX template upload from requestor side to prefill form fields.
     * Accepts a DOCX file that contains Content Controls (Structured Document Tags)
     * where the TAG (alias or title) matches the dynamic form field_name.
     * Returns JSON with mapped values for AJAX prefill.
     */
    public function uploadDocx($formCode)
    {
    // Normalize method casing because some servers/framework layers may provide
    // the method in uppercase which can cause strict comparisons to fail.
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
            // Return detailed debug info so front-end can show what the server sees
            $serverMethod = $_SERVER['REQUEST_METHOD'] ?? null;
            $allHeaders = function_exists('getallheaders') ? getallheaders() : [];
            $debug = [
                'error' => 'Method not allowed',
                'method_seen_by_ci' => $method,
                'server_request_method' => $serverMethod,
                'headers' => $allHeaders,
                'csrf_name' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ];
            // Log debug details to application log for server-side inspection
            try {
                log_message('debug', 'uploadDocx debug: ' . json_encode($debug));
            } catch (\Throwable $e) {
                // ignore logging failures
            }
            return $this->response->setStatusCode(405)->setJSON($debug);
        }

        // Basic permission: only authenticated users (route already behind auth) – ensure form exists
        $form = $this->formModel->where('code', $formCode)->first();
        if (!$form) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Form not found']);
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
            // Use PhpWord to read DOCX XML directly for content controls
            // We parse word/document.xml and look for <w:sdt> blocks.
            $zip = new \ZipArchive();
            if ($zip->open($fullPath) === true) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();
                if ($xml) {
                    // Suppress namespace issues by registering namespaces
                    $doc = new \DOMDocument();
                    $doc->preserveWhiteSpace = false;
                    $doc->loadXML($xml);
                    $xpath = new \DOMXPath($doc);
                    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                    // Find all structured document tags
                    $nodes = $xpath->query('//w:sdt');
                    foreach ($nodes as $sdt) {
                        $tagName = null; // alias / tag to match field
                        // Tag from w:tag @w:val
                        $tagNode = $xpath->query('.//w:tag', $sdt)->item(0);
                        if ($tagNode && $tagNode->hasAttribute('w:val')) {
                            $tagName = trim($tagNode->getAttribute('w:val'));
                        }
                        // Fallback to alias/title stored in w:alias@w:val
                        if (!$tagName) {
                            $aliasNode = $xpath->query('.//w:alias', $sdt)->item(0);
                            if ($aliasNode && $aliasNode->hasAttribute('w:val')) {
                                $tagName = trim($aliasNode->getAttribute('w:val'));
                            }
                        }
                        if (!$tagName) {
                            continue; // no usable tag
                        }
                        // Extract plain text inside w:sdtContent
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
            log_message('error', 'DOCX parse error: ' . $e->getMessage());
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
                $updateData['approver_signature_date'] = date('Y-m-d H:i:s');
                $updateData['approval_comments'] = 'Auto-approved with service staff assignment';
            }
            
            $this->formSubmissionModel->update($submissionId, $updateData);

            // Ensure the assignment notification is created by using the model helper
            try {
                $this->formSubmissionModel->assignServiceStaff($submissionId, $serviceStaffId);
            } catch (\Exception $e) {
                log_message('error', 'Failed to create assignment notification in assignServiceStaff: ' . $e->getMessage());
            }

            // OPTIONAL: Auto-create a pending schedule when a service staff is assigned
            // This ensures consistency between both approval flows (submitApproval and assignServiceStaff)
            // Auto-create schedule only if enabled in config
            try {
                $appConf = config('App');
                if (!empty($appConf->autoCreateScheduleOnApproval) && class_exists('App\\Models\\ScheduleModel')) {
                    $scheduleModel = new \App\Models\ScheduleModel();
                    // Only insert if ScheduleModel allows submission_id
                    if (property_exists($scheduleModel, 'allowedFields') && in_array('submission_id', $scheduleModel->allowedFields)) {
                        // Check if a schedule already exists for this submission to avoid duplicates
                        $existingSchedule = $scheduleModel->where('submission_id', $submissionId)->first();
                        if (!$existingSchedule) {
                            $schedData = [
                                'submission_id' => $submissionId,
                                'scheduled_date' => date('Y-m-d'),
                                'scheduled_time' => '09:00:00',
                                'duration_minutes' => 60,
                                'assigned_staff_id' => $serviceStaffId,
                                'location' => '',
                                'notes' => 'Auto-created schedule on service staff assignment',
                                'status' => 'pending'
                            ];

                            // Insert quietly; if it fails, log but don't block assignment flow
                            try {
                                $scheduleModel->insert($schedData);
                                log_message('info', "Auto-created schedule for submission {$submissionId} assigned to service staff {$serviceStaffId}");
                            } catch (\Throwable $inner) {
                                log_message('error', 'Auto-schedule creation failed for submission ' . $submissionId . ': ' . $inner->getMessage());
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Non-fatal: log and continue
                log_message('error', 'Error while attempting to auto-create schedule in assignServiceStaff: ' . $e->getMessage());
            }

            log_message('info', "Service staff {$serviceStaff['full_name']} assigned to submission {$submissionId} by user {$userId}");

            return redirect()->back()->with('message', 'Service staff assigned successfully');
            
        } catch (\Exception $e) {
            log_message('error', 'Error in assignServiceStaff: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while assigning service staff');
        }
    }

    /**
     * Requestor cancels their request
     */
    public function cancelSubmission()
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');

        if ($userType !== 'requestor') {
            return redirect()->back()->with('error', 'Unauthorized action');
        }

        $submissionId = $this->request->getPost('submission_id');
        if (empty($submissionId)) {
            return redirect()->back()->with('error', 'Missing submission ID');
        }

        $submission = $this->formSubmissionModel->find($submissionId);
        if (!$submission) {
            return redirect()->back()->with('error', 'Submission not found');
        }

        // Ensure the submission belongs to the user and can be cancelled
        $result = $this->formSubmissionModel->cancelSubmission($submissionId, $userId);

        if ($result) {
            return redirect()->to('/forms/my-submissions')->with('message', 'Your request has been cancelled');
        }

        return redirect()->back()->with('error', 'Unable to cancel the request. It may have already been processed.');
    }

    /**
     * Permanently delete a submission and all related data (requestor only or admin)
     */
    public function deleteSubmission()
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        $submissionId = $this->request->getPost('submission_id');

        if (empty($submissionId)) {
            return redirect()->back()->with('error', 'Missing submission ID');
        }

        $submission = $this->formSubmissionModel->find($submissionId);
        if (!$submission) {
            return redirect()->back()->with('error', 'Submission not found');
        }

    // Authorization: requestor can delete own completed, rejected, or cancelled submissions; admin/superuser can delete any
    $allowedStatuses = ['completed', 'rejected', 'cancelled'];
        $isOwner = ($submission['submitted_by'] ?? null) == $userId;
        $isAdmin = in_array($userType, ['admin','superuser']);
    if (!($isAdmin || ($isOwner && in_array($submission['status'] ?? '', $allowedStatuses)))) {
            return redirect()->back()->with('error', 'You are not allowed to delete this submission');
        }

        $db = \Config\Database::connect();
        $db->transStart();
        try {
            // Delete related field data
            $this->formSubmissionDataModel->where('submission_id', $submissionId)->delete();

            // Delete feedback
            $feedbackModel = new \App\Models\FeedbackModel();
            $feedbackModel->where('submission_id', $submissionId)->delete();

            // Delete schedules referencing this submission
            if (class_exists('App\\Models\\ScheduleModel')) {
                $scheduleModel = new \App\Models\ScheduleModel();
                if (property_exists($scheduleModel, 'allowedFields') && in_array('submission_id', $scheduleModel->allowedFields)) {
                    $scheduleModel->where('submission_id', $submissionId)->delete();
                } else {
                    $scheduleModel->where('submission_id', $submissionId)->delete();
                }
            }

            // Delete notifications referencing this submission (best effort)
            if (class_exists('App\\Models\\NotificationModel')) {
                $notifModel = new \App\Models\NotificationModel();
                if (method_exists($notifModel, 'where')) {
                    $notifModel->where('submission_id', $submissionId)->delete();
                }
            }

            // Finally delete submission
            $this->formSubmissionModel->delete($submissionId);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Failed to delete submission ' . $submissionId . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete submission');
        }
        $db->transComplete();

        return redirect()->to('/forms/my-submissions')->with('message', 'Submission deleted successfully');
    }


}

