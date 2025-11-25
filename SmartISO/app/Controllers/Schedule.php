<?php

namespace App\Controllers;

use App\Models\ScheduleModel;
use App\Models\FormSubmissionModel;
use App\Models\UserModel;
use App\Models\NotificationModel;

class Schedule extends BaseController
{
    protected $scheduleModel;
    protected $submissionModel;
    protected $userModel;
    protected $notificationModel;

    public function __construct()
    {
        $this->scheduleModel = new ScheduleModel();
        $this->submissionModel = new FormSubmissionModel();
        $this->userModel = new UserModel();
        $this->notificationModel = new NotificationModel();
    }

    public function index()
    {
        $userType = session()->get('user_type');
        $userId = session()->get('user_id');
        $userDepartmentId = session()->get('department_id');
        $isGlobalAdmin = in_array($userType, ['admin', 'superuser']);
        $userDepartmentId = session()->get('department_id');
        $isGlobalAdmin = in_array($userType, ['admin', 'superuser']);
        
        // Department admin check - use the same logic as calendar() method
        $isDepartmentAdmin = ($userType === 'department_admin');
        
        $data['title'] = 'Service Schedules';
        
        log_message('debug', '==================== SCHEDULE INDEX ACCESS ====================');
        log_message('debug', 'Schedule Index - User Type: "' . $userType . '", User ID: ' . $userId . ', Department: ' . $userDepartmentId);
        log_message('debug', 'Schedule Index - isDepartmentAdmin: ' . ($isDepartmentAdmin ? 'TRUE' : 'FALSE'));
        
        // Admin and superuser can see all schedules AND submissions without schedules
        if ($isGlobalAdmin) {
            $schedules = $this->scheduleModel->getSchedulesWithDetails();
            
            // Also get submissions that don't have schedule entries yet
            $submissionsWithoutSchedules = $this->getSubmissionsWithoutSchedules();
            // Merge them into the schedules array
            $schedules = array_merge($schedules, $submissionsWithoutSchedules);
        }
        // Department admin sees schedules for their department
        elseif ($isDepartmentAdmin) {
            if ($userDepartmentId) {
                log_message('info', 'Department Admin Index START - User ID: ' . $userId . ' | Department: ' . $userDepartmentId);
                
                $schedules = $this->scheduleModel->getDepartmentSchedules($userDepartmentId);
                log_message('info', 'Department Admin Index - getDepartmentSchedules returned: ' . count($schedules) . ' schedule(s)');
                
                // Also get submissions without schedules from their department
                $submissionsWithoutSchedules = $this->getDepartmentSubmissionsWithoutSchedules($userDepartmentId);
                log_message('info', 'Department Admin Index - getDepartmentSubmissionsWithoutSchedules returned: ' . count($submissionsWithoutSchedules) . ' submission(s)');
                
                $schedules = array_merge($schedules, $submissionsWithoutSchedules);
                
                log_message('info', 'Department Admin Index END - Total schedules: ' . count($schedules));
            } else {
                log_message('warning', 'Department Admin Index - No department_id in session');
                $schedules = [];
            }
        }
        // Service staff sees schedules assigned to them
        elseif ($userType === 'service_staff') {
            // IMPORTANT: Service staff see ALL submissions assigned to them
            // NO department or office filtering - assignment is based on service_staff_id only
            $schedules = $this->scheduleModel->getStaffSchedules($userId);
            
            log_message('info', 'Service Staff Calendar - User ID: ' . $userId . ' | Schedules from getStaffSchedules: ' . count($schedules));
            
            // Also get submissions assigned to this service staff that don't have schedules yet
            $submissionsWithoutSchedules = $this->getServiceStaffSubmissionsWithoutSchedules($userId);
            
            log_message('info', 'Service Staff Calendar - User ID: ' . $userId . ' | Submissions without schedules: ' . count($submissionsWithoutSchedules));
            
            // Merge and deduplicate by submission_id to prevent duplicates
            // If a submission has both a schedule entry and appears in submissions list, keep the schedule version
            $merged = $schedules;
            foreach ($submissionsWithoutSchedules as $sub) {
                $submissionId = $sub['submission_id'] ?? null;
                $isDuplicate = false;
                if ($submissionId) {
                    foreach ($schedules as $sched) {
                        if (($sched['submission_id'] ?? null) == $submissionId) {
                            $isDuplicate = true;
                            break;
                        }
                    }
                }
                if (!$isDuplicate) {
                    $merged[] = $sub;
                }
            }
            $schedules = $merged;
            
            log_message('info', 'Service Staff Calendar - User ID: ' . $userId . ' | Total schedules after merge: ' . count($schedules));
        }
        // Requestor sees schedules for their submissions
        elseif ($userType === 'requestor') {
            $submissions = $this->submissionModel->where('submitted_by', $userId)->findAll();
            $submissionIds = array_column($submissions, 'id');
            if (!empty($submissionIds)) {
                // Use the new method to get schedules with full details
                $schedules = $this->scheduleModel->getSchedulesBySubmissions($submissionIds);
                
                // ALWAYS show submissions without schedules as placeholder events
                // This ensures new submissions appear immediately on the calendar
                $submissionsWithoutSchedules = $this->getRequestorSubmissionsWithoutSchedules($userId, $submissionIds);
                $schedules = array_merge($schedules, $submissionsWithoutSchedules);
            } else {
                $schedules = [];
            }
        }
        // Approving authority sees schedules for submissions they need to approve OR have approved (filtered by department)
        elseif ($userType === 'approving_authority') {
            // Get submissions that need approval OR already approved by this user
            $builder = $this->submissionModel->builder();
            $builder->select('form_submissions.*')
                    ->join('users', 'users.id = form_submissions.submitted_by')
                    ->join('form_signatories fsig', 'fsig.form_id = form_submissions.form_id AND fsig.user_id = ' . $userId, 'inner')
                    ->groupStart()
                        ->whereIn('form_submissions.status', ['submitted', 'approved', 'completed']) // Show submitted, approved and completed for approvers
                            ->orWhere('form_submissions.approver_id', $userId) // Already approved by this user
                    ->groupEnd();
            
            // Filter by department for non-admin approvers
            if (!$isGlobalAdmin && $userDepartmentId) {
                $builder->where('users.department_id', $userDepartmentId);
            }
            
            $submissions = $builder->get()->getResultArray();
            $submissionIds = array_column($submissions, 'id');
            if (!empty($submissionIds)) {
                // Use the new method to get schedules with full details
                $schedules = $this->scheduleModel->getSchedulesBySubmissions($submissionIds);
                
                // Also get submissions without schedules yet
                $submissionsWithoutSchedules = $this->getApproverSubmissionsWithoutSchedules($userId, $userDepartmentId, $isGlobalAdmin, $submissionIds);
                $schedules = array_merge($schedules, $submissionsWithoutSchedules);
            } else {
                $schedules = [];
            }
        }
        else {
            log_message('warning', 'Schedule Index - ELSE BLOCK TRIGGERED for user_type: "' . $userType . '" | Showing ALL schedules');
            $schedules = $this->scheduleModel->getSchedulesWithDetails();
        }

        // If none found, fallback to pending schedules for next 30 days
        // EXCEPT for department admins - they should only see their department's schedules (even if empty)
        if (empty($schedules) && !$isDepartmentAdmin) {
            $start = date('Y-m-d');
            $end = date('Y-m-d', strtotime('+30 days'));
            $schedules = $this->scheduleModel->getPendingSchedules($start, $end);
            log_message('info', 'Schedule Index - Using fallback pending schedules for user type: ' . $userType);
        }
        
        // ADDITIONAL SAFEGUARD: For department admins, filter out any schedules from other departments
        if ($isDepartmentAdmin && !empty($schedules) && $userDepartmentId) {
            $beforeCount = count($schedules);
            $schedules = array_filter($schedules, function($schedule) use ($userDepartmentId) {
                // Check form_department_id (primary) - forms belong to departments
                if (isset($schedule['form_department_id'])) {
                    return $schedule['form_department_id'] == $userDepartmentId;
                }
                // Fallback to requestor_department_id if form_department_id not available
                if (isset($schedule['requestor_department_id'])) {
                    return $schedule['requestor_department_id'] == $userDepartmentId;
                }
                // If neither available, allow through (shouldn't happen with proper joins)
                return true;
            });
            $schedules = array_values($schedules); // Re-index array
            $afterCount = count($schedules);
            
            if ($beforeCount != $afterCount) {
                log_message('warning', 'Department Admin Index - SAFEGUARD FILTER ACTIVATED: Removed ' . ($beforeCount - $afterCount) . ' cross-department schedule(s)');
            }
        }

        $calendarEvents = [];
        // Track submission IDs present on this user's calendar so view handlers
        // can allow access when the event is shown in the calendar.
        $visibleSubmissionIds = [];
        foreach ($schedules as $schedule) {
            // Ensure we have minimum required fields
            if (empty($schedule['id']) || empty($schedule['scheduled_date']) || empty($schedule['scheduled_time'])) {
                log_message('warning', 'Skipping schedule due to missing required fields: ' . json_encode($schedule));
                continue;
            }
            
            $title = ($schedule['priority'] ?? 0) ? '★ ' : '';
            // Use form description (actual title) instead of form_code
            $title .= $schedule['form_description'] ?? $schedule['panel_name'] ?? $schedule['form_code'] ?? 'Service';

            // Use submission_status if available, otherwise fall back to schedule status
            $status = $schedule['submission_status'] ?? $schedule['status'] ?? 'pending';

            // compute view URL for this submission (if available)
            // NOTE: if a submission is included in the calendar for the current user,
            // it should be viewable. Mark can_view true for all calendar events so
            // the frontend will always show the "View Request" action when present.
            $submissionId = $schedule['submission_id'] ?? null;
            $submittedBy = $schedule['submitted_by'] ?? null;

            $canView = true; // calendar events are assumed viewable by the viewer

            // Build a view URL that points to admin view for admins only, otherwise to public forms view
            $viewBase = in_array($userType, ['admin','superuser']) ? base_url('admin/dynamicforms/view-submission/') : base_url('forms/submission/');
            $viewUrl = $submissionId ? ($viewBase . $submissionId) : null;

            // (already computed above) Add this submission to the visible list
            if (!empty($schedule['submission_id'])) {
                $visibleSubmissionIds[] = (int)$schedule['submission_id'];
            }

            $calendarEvents[] = [
                'id' => $schedule['id'],
                'title' => $title,
                'start' => $schedule['scheduled_date'] . 'T' . $schedule['scheduled_time'],
                'description' => $schedule['notes'] ?? null,
                'requestor_name' => $schedule['requestor_name'] ?? null,
                'submitted_by' => $submittedBy,
                'requestor_department' => $schedule['requestor_department_name'] ?? ($schedule['requestor_department'] ?? null),
                'submission_date' => isset($schedule['submission_created_at']) ? date('M d, Y h:i A', strtotime($schedule['submission_created_at'])) : (isset($schedule['created_at']) ? date('M d, Y h:i A', strtotime($schedule['created_at'])) : null),
                'submission_id' => $schedule['submission_id'] ?? null,
                'status' => $status,
                'priority' => (int)($schedule['priority'] ?? 0),
                'estimated_date' => $schedule['estimated_date'] ?? null,
                'eta_days' => isset($schedule['eta_days']) ? (int)$schedule['eta_days'] : null,
                'priority_level' => $schedule['priority_level'] ?? null,
                'scheduled_time' => $schedule['scheduled_time'] ?? null,
                'is_manual_schedule' => isset($schedule['is_manual_schedule']) ? (int)$schedule['is_manual_schedule'] : 0,
                'can_view' => $canView,
                'view_url' => $viewUrl
            ];
        }
        
        log_message('info', 'Index calendar events created: ' . count($calendarEvents) . ' from ' . count($schedules) . ' schedules');
        if (count($schedules) > count($calendarEvents)) {
            log_message('warning', 'Some schedules were skipped in index. Schedules: ' . count($schedules) . ', Events: ' . count($calendarEvents));
        }

        // Persist visible submissions in the user's session for the session duration
        session()->set('calendar_visible_submissions', array_values(array_unique($visibleSubmissionIds)));

        $data['events'] = json_encode($calendarEvents);
        $data['events_count'] = count($calendarEvents);
        $data['isDepartmentFiltered'] = !$isGlobalAdmin;

        $data['title'] = 'Schedule Calendar';
        return view('schedule/calendar', $data);
    }

    public function create($submissionId = null)
    {
        if (!$submissionId) {
            return redirect()->back()->with('error', 'Invalid submission ID');
        }

        $submission = $this->submissionModel->find($submissionId);
        if (!$submission) {
            return redirect()->back()->with('error', 'Submission not found');
        }

        $data['title'] = 'Schedule Service';
        $data['submission'] = $submission;
        $data['staff'] = $this->userModel->getUsersByType('service_staff');
        
        return view('schedule/create', $data);
    }

    public function store()
    {
    $validation = $this->validate([
            'submission_id'      => 'required|integer',
            'scheduled_date'     => 'required|valid_date',
            'scheduled_time'     => 'required',
            'duration_minutes'   => 'permit_empty|integer',
            'assigned_staff_id'  => 'required|integer',
            'location'          => 'permit_empty|max_length[255]',
            'notes'             => 'permit_empty'
        ]);

        if (!$validation) {
            return redirect()->back()
                           ->withInput()
                           ->with('errors', $this->validator->getErrors());
        }

    // Parse JSON body for AJAX clients (safe fallback when body isn't valid JSON)
    $jsonBody = [];
    try {
        $raw = $this->request->getBody();
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $jsonBody = $decoded;
            }
        }
    } catch (\Exception $e) {
        // Leave $jsonBody as empty array on error
        log_message('debug', 'Schedule::store safe JSON parse failed: ' . $e->getMessage());
    }

    // Priority level mapping defaults
    $priorityLevel = $this->request->getPost('priority_level') ?: ($jsonBody['priority_level'] ?? null); // expected: high|medium|low
    
    // Check if this is a manual schedule (user explicitly set a date)
    $isManualSchedule = $this->request->getPost('is_manual_schedule') ?: ($jsonBody['is_manual_schedule'] ?? false);

        $data = [
            'submission_id'      => $this->request->getPost('submission_id'),
            'scheduled_date'     => $this->request->getPost('scheduled_date'),
            'scheduled_time'     => $this->request->getPost('scheduled_time'),
            'duration_minutes'   => $this->request->getPost('duration_minutes') ?: 60,
            'assigned_staff_id'  => $this->request->getPost('assigned_staff_id'),
            'location'          => $this->request->getPost('location'),
            'notes'             => $this->request->getPost('notes'),
            'status'            => 'confirmed',
            'is_manual_schedule' => $isManualSchedule ? 1 : 0
        ];

        // Compute ETA from priority_level if provided AND not manually scheduled
        // When manually scheduled, target completion date = scheduled date
        if ($priorityLevel && !$isManualSchedule) {
            // New mapping per request:
            // low  => today + 1 week (7 calendar days)
            // medium => within 5 working days (business days)
            // high => within 2 business days
            $etaDays = null; $estimatedDate = null;
            if ($priorityLevel === 'low') {
                $etaDays = 7;
                $estimatedDate = date('Y-m-d', strtotime($data['scheduled_date'] . ' +7 days'));
            } elseif ($priorityLevel === 'medium') {
                $etaDays = 5;
                $estimatedDate = $this->addBusinessDays($data['scheduled_date'], 5);
            } elseif ($priorityLevel === 'high') {
                $etaDays = 3;
                $estimatedDate = $this->addBusinessDays($data['scheduled_date'], 3);
            }
            if ($etaDays && $estimatedDate) {
                $data['eta_days'] = $etaDays;
                $data['priority_level'] = $priorityLevel;
                $data['estimated_date'] = $estimatedDate;
            }
        } elseif ($isManualSchedule) {
            // For manual schedules, target completion date = scheduled date
            $data['estimated_date'] = $data['scheduled_date'];
            $data['eta_days'] = 0; // Same day
            if ($priorityLevel) {
                $data['priority_level'] = $priorityLevel;
            }
        }

        // Check for conflicts
        $conflicts = $this->scheduleModel->checkConflicts(
            $data['assigned_staff_id'],
            $data['scheduled_date'],
            $data['scheduled_time'],
            $data['duration_minutes']
        );

        if ($conflicts) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Schedule conflict detected. Please choose a different time.');
        }

        $insertId = $this->scheduleModel->insert($data, true);
        if ($insertId) {
            // Get submission details for notification
            $submission = $this->submissionModel->find($data['submission_id']);

            // Create notification for requestor
            $this->notificationModel->createScheduleNotification(
                $insertId,
                $submission['submitted_by'],
                $data['scheduled_date'],
                $data['scheduled_time']
            );

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => true, 'id' => $insertId, 'estimated_date' => $data['estimated_date'] ?? null, 'eta_days' => $data['eta_days'] ?? null, 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
            }

            return redirect()->to('/schedule')
                           ->with('success', 'Service scheduled successfully');
        }

        return redirect()->back()
                       ->withInput()
                       ->with('error', 'Failed to create schedule');
    }

    public function edit($id)
    {
        $schedule = $this->scheduleModel->find($id);
        if (!$schedule) {
            return redirect()->back()->with('error', 'Schedule not found');
        }

        $data['title'] = 'Edit Schedule';
        $data['schedule'] = $schedule;
        $data['submission'] = $this->submissionModel->find($schedule['submission_id']);
        $data['staff'] = $this->userModel->getUsersByType('service_staff');
        
        return view('schedule/edit', $data);
    }

    public function update($id)
    {
        $schedule = $this->scheduleModel->find($id);
        if (!$schedule) {
            return redirect()->back()->with('error', 'Schedule not found');
        }

    // Parse JSON body for AJAX updates (safe)
    $jsonBody = [];
    try {
        $raw = $this->request->getBody();
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $jsonBody = $decoded;
            }
        }
    } catch (\Exception $e) {
        log_message('debug', 'Schedule::update safe JSON parse failed: ' . $e->getMessage());
    }

    $validation = $this->validate([
            'scheduled_date'     => 'required|valid_date',
            'scheduled_time'     => 'required',
            'duration_minutes'   => 'permit_empty|integer',
            'assigned_staff_id'  => 'required|integer',
            'location'          => 'permit_empty|max_length[255]',
            'notes'             => 'permit_empty',
            'status'            => 'required|in_list[pending,confirmed,in_progress,completed,cancelled]'
        ]);

        if (!$validation) {
            return redirect()->back()
                           ->withInput()
                           ->with('errors', $this->validator->getErrors());
        }

        $priorityLevel = $this->request->getPost('priority_level') ?: ($jsonBody['priority_level'] ?? null);
        
        // Check if this is a manual schedule (user explicitly set a date)
        $isManualSchedule = $this->request->getPost('is_manual_schedule') ?: ($jsonBody['is_manual_schedule'] ?? false);

        $data = [
            'scheduled_date'     => $this->request->getPost('scheduled_date') ?: ($jsonBody['scheduled_date'] ?? $schedule['scheduled_date']),
            'scheduled_time'     => $this->request->getPost('scheduled_time'),
            'duration_minutes'   => $this->request->getPost('duration_minutes') ?: 60,
            'assigned_staff_id'  => $this->request->getPost('assigned_staff_id'),
            'location'          => $this->request->getPost('location'),
            'notes'             => $this->request->getPost('notes'),
            'status'            => $this->request->getPost('status'),
            'is_manual_schedule' => $isManualSchedule ? 1 : 0
        ];

        // Compute ETA from priority_level if provided AND not manually scheduled
        // When manually scheduled, target completion date = scheduled date
        if ($priorityLevel && !$isManualSchedule) {
            $scheduledDateForEta = $data['scheduled_date'] ?: $schedule['scheduled_date'];
            $etaDays = null; $estimatedDate = null;
            if ($priorityLevel === 'low') {
                $etaDays = 7;
                $estimatedDate = date('Y-m-d', strtotime($scheduledDateForEta . ' +7 days'));
            } elseif ($priorityLevel === 'medium') {
                $etaDays = 5;
                $estimatedDate = $this->addBusinessDays($scheduledDateForEta, 5);
            } elseif ($priorityLevel === 'high') {
                $etaDays = 3;
                $estimatedDate = $this->addBusinessDays($scheduledDateForEta, 3);
            }
            if ($etaDays && $estimatedDate) {
                $data['eta_days'] = $etaDays;
                $data['priority_level'] = $priorityLevel;
                $data['estimated_date'] = $estimatedDate;
            }
        } elseif ($isManualSchedule) {
            // For manual schedules, target completion date = scheduled date
            $scheduledDateForManual = $data['scheduled_date'] ?: $schedule['scheduled_date'];
            $data['estimated_date'] = $scheduledDateForManual;
            $data['eta_days'] = 0; // Same day
            if ($priorityLevel) {
                $data['priority_level'] = $priorityLevel;
            }
        }

        // Check for conflicts (excluding current schedule)
        $conflicts = $this->scheduleModel->checkConflicts(
            $data['assigned_staff_id'],
            $data['scheduled_date'],
            $data['scheduled_time'],
            $data['duration_minutes'],
            $id
        );

        if ($conflicts) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Schedule conflict detected. Please choose a different time.');
        }

        if ($this->scheduleModel->update($id, $data)) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => true, 'estimated_date' => $data['estimated_date'] ?? null, 'eta_days' => $data['eta_days'] ?? null, 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
            }

            return redirect()->to('/schedule')
                           ->with('success', 'Schedule updated successfully');
        }

        return redirect()->back()
                       ->withInput()
                       ->with('error', 'Failed to update schedule');
    }

    public function delete($id)
    {
        $schedule = $this->scheduleModel->find($id);
        if (!$schedule) {
            return redirect()->back()->with('error', 'Schedule not found');
        }

        if ($this->scheduleModel->delete($id)) {
            return redirect()->to('/schedule')
                           ->with('success', 'Schedule deleted successfully');
        }

        return redirect()->back()
                       ->with('error', 'Failed to delete schedule');
    }

    public function calendar()
    {
        $userType = session()->get('user_type');
        $userId = session()->get('user_id');
        
        $data['title'] = 'Schedule Calendar';
        
        // DEBUG: Log session info with details
        log_message('debug', '==================== CALENDAR ACCESS START ====================');
        log_message('debug', 'Calendar accessed - User Type: "' . $userType . '" (strlen=' . strlen($userType) . '), User ID: ' . $userId);
        log_message('debug', 'User Type comparison: is_department_admin=' . ($userType === 'department_admin' ? 'TRUE' : 'FALSE'));
        log_message('debug', 'Department ID from session: ' . session()->get('department_id'));
        log_message('debug', 'All session data: ' . json_encode(session()->get()));
        
        // Initialize schedules array
        $schedules = [];
        
        // Admin and superuser can see all schedules AND submissions without schedules
        if (in_array($userType, ['admin', 'superuser'])) {
            $schedules = $this->scheduleModel->getSchedulesWithDetails();
            
            // Also get submissions that don't have schedule entries yet
            $submissionsWithoutSchedules = $this->getSubmissionsWithoutSchedules();
            // Merge them into the schedules array
            $schedules = array_merge($schedules, $submissionsWithoutSchedules);
        }
        // Department admin sees schedules for their department only
        elseif ($userType === 'department_admin') {
            $userDepartmentId = session()->get('department_id');
            if ($userDepartmentId) {
                log_message('info', 'Department Admin Calendar START - User ID: ' . $userId . ' | User Type: ' . $userType . ' | Department: ' . $userDepartmentId);
                
                $schedules = $this->scheduleModel->getDepartmentSchedules($userDepartmentId);
                log_message('info', 'Department Admin Calendar - getDepartmentSchedules returned: ' . count($schedules) . ' schedule(s)');
                
                // Also get submissions without schedules from their department
                $submissionsWithoutSchedules = $this->getDepartmentSubmissionsWithoutSchedules($userDepartmentId);
                log_message('info', 'Department Admin Calendar - getDepartmentSubmissionsWithoutSchedules returned: ' . count($submissionsWithoutSchedules) . ' submission(s)');
                
                $schedules = array_merge($schedules, $submissionsWithoutSchedules);
                
                log_message('info', 'Department Admin Calendar END - User ID: ' . $userId . ' | Department: ' . $userDepartmentId . ' | Total schedules: ' . count($schedules));
                
                // Log each schedule/submission for debugging
                foreach ($schedules as $idx => $sched) {
                    log_message('debug', 'Department Admin Calendar Item ' . ($idx + 1) . ': Submission ID: ' . ($sched['submission_id'] ?? 'N/A') . ' | Form: ' . ($sched['form_code'] ?? 'N/A') . ' | Status: ' . ($sched['status'] ?? 'N/A'));
                }
            } else {
                // No department assigned, show empty calendar
                $schedules = [];
                log_message('warning', 'Department Admin Calendar - User ID: ' . $userId . ' has no department assigned');
            }
        }
        // Service staff sees schedules assigned to them
        elseif ($userType === 'service_staff') {
            // IMPORTANT: Service staff see ALL schedules assigned to them
            // NO department or office filtering - only filter by assigned_staff_id
            $schedules = $this->scheduleModel->getStaffSchedules($userId);
            
            // Debug: Log raw schedules from database
            log_message('debug', 'Service Staff Schedules (User ID: ' . $userId . '): ' . json_encode([
                'count' => count($schedules),
                'schedules' => $schedules
            ]));
            
            log_message('info', 'Service Staff Calendar (calendar method) - User ID: ' . $userId . ' | Schedules from getStaffSchedules: ' . count($schedules));
            
            // Also get submissions assigned to this service staff that don't have schedules yet
            $submissionsWithoutSchedules = $this->getServiceStaffSubmissionsWithoutSchedules($userId);
            
            log_message('info', 'Service Staff Calendar (calendar method) - User ID: ' . $userId . ' | Submissions without schedules: ' . count($submissionsWithoutSchedules));
            
            // Merge and deduplicate by submission_id to prevent duplicates
            // If a submission has both a schedule entry and appears in submissions list, keep the schedule version
            $merged = $schedules;
            foreach ($submissionsWithoutSchedules as $sub) {
                $submissionId = $sub['submission_id'] ?? null;
                $isDuplicate = false;
                if ($submissionId) {
                    foreach ($schedules as $sched) {
                        if (($sched['submission_id'] ?? null) == $submissionId) {
                            $isDuplicate = true;
                            break;
                        }
                    }
                }
                if (!$isDuplicate) {
                    $merged[] = $sub;
                }
            }
            $schedules = $merged;
            
            log_message('info', 'Service Staff Calendar (calendar method) - User ID: ' . $userId . ' | Total schedules: ' . count($schedules));
        }
        // Requestor sees schedules for their submissions
        elseif ($userType === 'requestor') {
            $submissions = $this->submissionModel->where('submitted_by', $userId)->findAll();
            $submissionIds = array_column($submissions, 'id');
            if (!empty($submissionIds)) {
                // Use the new method to get schedules with full details
                $schedules = $this->scheduleModel->getSchedulesBySubmissions($submissionIds);
                
                // ALWAYS show submissions without schedules as placeholder events
                // This ensures new submissions appear immediately on the calendar
                $submissionsWithoutSchedules = $this->getRequestorSubmissionsWithoutSchedules($userId, $submissionIds);
                $schedules = array_merge($schedules, $submissionsWithoutSchedules);
            } else {
                $schedules = [];
            }
        }
        // Approving authority sees schedules for submissions they need to approve OR have approved
        elseif ($userType === 'approving_authority') {
            $builder = $this->submissionModel->builder();
            $builder->select('form_submissions.*')
                    ->join('users', 'users.id = form_submissions.submitted_by', 'left')
                    ->join('form_signatories fsig', 'fsig.form_id = form_submissions.form_id AND fsig.user_id = ' . $userId, 'inner')
                    ->groupStart()
                    ->whereIn('form_submissions.status', ['submitted','approved','completed']) // Pending/approved/completed
                    ->orWhere('form_submissions.approver_id', $userId) // Already approved
                ->groupEnd();

            // If this approver is not a global admin, restrict to their department
            if (!$isGlobalAdmin && $userDepartmentId) {
                $builder->where('users.department_id', $userDepartmentId);
            }
            
            $submissions = $builder->get()->getResultArray();
            $submissionIds = array_column($submissions, 'id');
            if (!empty($submissionIds)) {
                // Use the new method to get schedules with full details
                $schedules = $this->scheduleModel->getSchedulesBySubmissions($submissionIds);
                
                // Also get submissions without schedules yet (filtered by department when applicable)
                $submissionsWithoutSchedules = $this->getApproverSubmissionsWithoutSchedules($userId, $userDepartmentId, $isGlobalAdmin, $submissionIds);
                $schedules = array_merge($schedules, $submissionsWithoutSchedules);
            } else {
                $schedules = [];
            }
        }
        else {
            log_message('warning', 'Calendar - ELSE BLOCK TRIGGERED for user_type: "' . $userType . '" | This means the user_type did not match any of the elseif conditions above!');
            log_message('warning', 'Calendar - Falling back to getSchedulesWithDetails() which returns ALL schedules');
            log_message('warning', 'Calendar - User ID: ' . $userId . ' | Expected user_type values: admin, superuser, department_admin, service_staff, requestor, approving_authority');
            $schedules = $this->scheduleModel->getSchedulesWithDetails();
            log_message('warning', 'Calendar - Retrieved ' . count($schedules) . ' schedules from getSchedulesWithDetails()');
        }

        // If no schedules found, try to fetch pending schedules for the next 30 days as a fallback
        // EXCEPT for department admins - they should only see their department's schedules (even if empty)
        if (empty($schedules) && $userType !== 'department_admin') {
            $start = date('Y-m-d');
            $end = date('Y-m-d', strtotime('+30 days'));
            $schedules = $this->scheduleModel->getPendingSchedules($start, $end);
            log_message('info', 'Calendar - Using fallback pending schedules for user type: ' . $userType);
        }
        
        // DEBUG: Log schedules before formatting
        log_message('debug', 'Calendar - Schedules count before formatting: ' . count($schedules ?? []));
        log_message('debug', 'Calendar - User type for filtering check: ' . $userType);
        
        // ADDITIONAL SAFEGUARD: For department admins, filter out any schedules from other departments
        // This ensures cross-department items don't slip through even if there's a bug elsewhere
        // Filter by FORM's department, not requestor's department
        if ($userType === 'department_admin' && !empty($schedules)) {
            $userDepartmentId = session()->get('department_id');
            if ($userDepartmentId) {
                $beforeCount = count($schedules);
                $schedules = array_filter($schedules, function($schedule) use ($userDepartmentId) {
                    // Check form_department_id (primary) - forms belong to departments
                    if (isset($schedule['form_department_id'])) {
                        return $schedule['form_department_id'] == $userDepartmentId;
                    }
                    // Fallback to requestor_department_id if form_department_id not available
                    if (isset($schedule['requestor_department_id'])) {
                        return $schedule['requestor_department_id'] == $userDepartmentId;
                    }
                    // If neither available, allow through (shouldn't happen with proper joins)
                    return true;
                });
                $schedules = array_values($schedules); // Re-index array
                $afterCount = count($schedules);
                
                if ($beforeCount != $afterCount) {
                    log_message('warning', 'Department Admin Calendar - SAFEGUARD FILTER ACTIVATED: Removed ' . ($beforeCount - $afterCount) . ' cross-department schedule(s)');
                }
            }
        }
        
        log_message('debug', 'Calendar - Schedules data: ' . json_encode($schedules ?? []));
        
        // Format schedules for calendar display
        $calendarEvents = [];
        $visibleSubmissionIds = [];
        foreach ($schedules as $schedule) {
            // Ensure we have minimum required fields
            if (empty($schedule['id']) || empty($schedule['scheduled_date']) || empty($schedule['scheduled_time'])) {
                log_message('warning', 'Skipping schedule due to missing required fields: ' . json_encode($schedule));
                continue;
            }
            
            $title = ($schedule['priority'] ?? 0) ? '★ ' : '';
            // Use form description (actual title) instead of form_code
            $title .= $schedule['form_description'] ?? $schedule['panel_name'] ?? $schedule['form_code'] ?? 'Service';

            // Use submission_status if available, otherwise fall back to schedule status
            $status = $schedule['submission_status'] ?? $schedule['status'] ?? 'pending';

            // compute view fields for calendar events
            $submissionId = $schedule['submission_id'] ?? null;
            $submittedBy = $schedule['submitted_by'] ?? null;
            $canView = true; // visible on calendar so allow view
            $viewBase = in_array($userType, ['admin','superuser']) ? base_url('admin/dynamicforms/view-submission/') : base_url('forms/submission/');
            $viewUrl = $submissionId ? ($viewBase . $submissionId) : null;

            if (!empty($submissionId)) {
                $visibleSubmissionIds[] = (int)$submissionId;
            }

            $calendarEvents[] = [
                'id' => $schedule['id'],
                'title' => $title,
                'start' => $schedule['scheduled_date'] . 'T' . $schedule['scheduled_time'],
                'description' => $schedule['notes'] ?? '',
                'requestor_name' => $schedule['requestor_name'] ?? null,
                'submission_id' => $schedule['submission_id'] ?? null,
                'status' => $status,
                'priority' => (int)($schedule['priority'] ?? 0),
                'estimated_date' => $schedule['estimated_date'] ?? null,
                'eta_days' => isset($schedule['eta_days']) ? (int)$schedule['eta_days'] : null,
                'priority_level' => $schedule['priority_level'] ?? null,
                'submission_id' => $schedule['submission_id'] ?? null,
                'scheduled_time' => $schedule['scheduled_time'] ?? '09:00:00',
                'is_manual_schedule' => $schedule['is_manual_schedule'] ?? 0,
                'can_view' => $canView,
                'view_url' => $viewUrl
            ];
        }
        
        log_message('info', 'Calendar events created: ' . count($calendarEvents) . ' from ' . count($schedules) . ' schedules');
        if (count($schedules) > count($calendarEvents)) {
            log_message('warning', 'Some schedules were skipped. Schedules: ' . count($schedules) . ', Events: ' . count($calendarEvents));
        }
        
        // Persist the visible submission IDs for the calendar view so view handlers
        // can allow access to submissions that were displayed in the calendar.
        session()->set('calendar_visible_submissions', array_values(array_unique($visibleSubmissionIds)));

        $data['events'] = json_encode($calendarEvents);
        $data['events_count'] = count($calendarEvents);
        
        // Debug data for browser console
        $data['debug_info'] = [
            'user_type' => $userType,
            'user_id' => $userId,
            'raw_schedules_count' => isset($schedules) ? count($schedules) : 0,
            'calendar_events_count' => count($calendarEvents),
            'schedules_sample' => isset($schedules) ? array_slice($schedules, 0, 3) : [],
            'session_data' => [
                'user_type' => session()->get('user_type'),
                'user_id' => session()->get('user_id'),
                'department_id' => session()->get('department_id'),
                'is_department_admin' => session()->get('is_department_admin')
            ]
        ];
        
        // DEBUG: Log final counts
        log_message('debug', 'Calendar - Final event count: ' . count($calendarEvents) . ' for user type: ' . $userType);
        
        return view('schedule/calendar', $data);
    }

    /**
     * Add N business days to a date (skip Saturday/Sunday)
     * Made public so other controllers can use it for ETA calculations
     * @param string $date YYYY-MM-DD
     * @param int $days
     * @return string YYYY-MM-DD
     */
    public function addBusinessDays(string $date, int $days): string
    {
        $ts = strtotime($date);
        $added = 0;
        while ($added < $days) {
            $ts = strtotime('+1 day', $ts);
            $dow = (int)date('N', $ts); // 1 (Mon) - 7 (Sun)
            if ($dow <= 5) { $added++; }
        }
        return date('Y-m-d', $ts);
    }

    /**
     * Get submissions that don't have schedule entries yet
     * This ensures admin can see ALL submissions in the schedule view
     */
    private function getSubmissionsWithoutSchedules()
    {
        $db = \Config\Database::connect();
        
        // Find submissions that don't have a corresponding schedule entry
        $builder = $db->table('form_submissions fs');
        $builder->select('fs.id as submission_id, fs.form_id, fs.panel_name, fs.status as submission_status,
                          fs.created_at, fs.created_at as submission_created_at, fs.priority,
                          f.code as form_code, f.description as form_description,
                          u.full_name as requestor_name, u.department_id as requestor_department_id, d.description as requestor_department_name')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->where('NOT EXISTS (SELECT 1 FROM schedules s WHERE s.submission_id = fs.id)', null, false)
            ->whereIn('fs.status', ['submitted', 'approved', 'pending_service', 'completed']) // Include completed submissions
            ->orderBy('fs.created_at', 'DESC');
        
        $results = $builder->get()->getResultArray();
        
        // Format these submissions as "virtual" schedule entries
        $virtualSchedules = [];
        foreach ($results as $row) {
            // Use submission created date as the scheduled date
            $createdDate = substr($row['created_at'], 0, 10);
            
            $virtualSchedules[] = [
                'id' => 'sub-' . $row['submission_id'], // Prefix with 'sub-' to distinguish from real schedules
                'submission_id' => $row['submission_id'],
                'submitted_by' => $row['submitted_by'] ?? null,
                'submitted_by' => $row['submitted_by'] ?? null,
                'form_id' => $row['form_id'],
                'panel_name' => $row['panel_name'],
                'submission_status' => $row['submission_status'],
                'form_code' => $row['form_code'],
                'form_description' => $row['form_description'],
                'requestor_name' => $row['requestor_name'],
                'requestor_department' => $row['requestor_department_name'] ?? null,
                'submission_created_at' => $row['submission_created_at'] ?? $row['created_at'] ?? null,
                'scheduled_date' => $createdDate,
                'scheduled_time' => '09:00:00', // Default time
                'duration_minutes' => 60,
                'location' => '',
                'notes' => 'Pending schedule assignment',
                'status' => $row['submission_status'] ?? 'pending',
                'assigned_staff_id' => null,
                'assigned_staff_name' => null,
                'priority' => $row['priority'] ?? 0,
                'eta_days' => null,
                'estimated_date' => null,
                'priority_level' => null
            ];
        }
        
        return $virtualSchedules;
    }

    /**
     * Get ALL submissions assigned to a service staff member
     * IMPORTANT: Service staff see ALL submissions assigned to them regardless of requestor's department or office
     * This method now returns ALL assigned submissions to ensure nothing is missed on the calendar
     * Even if a schedule exists, we show the submission (schedules will be merged and deduplicated by ID)
     */
    private function getServiceStaffSubmissionsWithoutSchedules($staffId)
    {
        $db = \Config\Database::connect();
        
        // Find ALL submissions assigned to this service staff
        // NO DEPARTMENT, OFFICE, OR SCHEDULE FILTERING - only filter by service_staff_id
        $builder = $db->table('form_submissions fs');
        $builder->select('fs.id as submission_id, fs.form_id, fs.panel_name, fs.status as submission_status,
                  fs.created_at, fs.created_at as submission_created_at, fs.approved_at, fs.priority, fs.service_staff_id,
                  f.code as form_code, f.description as form_description,
                  u.full_name as requestor_name, u.department_id as requestor_department_id, d.description as requestor_department_name,
                  fsd.field_value as priority_level')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->join('form_submission_data fsd', 'fsd.submission_id = fs.id AND fsd.field_name = "priority_level"', 'left')
            ->where('fs.service_staff_id', $staffId)  // ONLY filter: assigned to this service staff
            ->whereIn('fs.status', ['approved', 'pending_service', 'completed']);
            // NOTE: intentionally include ALL submissions assigned to this staff member
            // (do NOT exclude those with existing schedules). The calendar merges
            // schedule rows and "virtual" submission rows and will keep the
            // schedule row when a real schedule exists. This handles cases where
            // a schedule exists but its assigned_staff_id is missing (so
            // getStaffSchedules() would not return it) — we still want the
            // submission to be visible to the assigned service staff.
        
        // REMOVED: NOT EXISTS check for schedules - we want ALL submissions to show
        // If a schedule exists from getStaffSchedules(), array_merge will handle it
        // but we ensure submissions appear even if schedule query fails
        
        // EXPLICITLY NO FILTERING by:
        // - u.department_id (requestor's department)
        // - u.office_id (requestor's office)  
        // - f.office_id (form's office)
        // - Existence of schedule entry
        // Service staff can be assigned to submissions from ANY department or office
        
        $builder->orderBy('fs.created_at', 'DESC');
        
        // Log the actual query being executed
        $sql = $builder->getCompiledSelect(false);
        log_message('debug', '[getServiceStaffSubmissionsWithoutSchedules] Query for staff_id=' . $staffId . ': ' . $sql);
        
        $results = $builder->get()->getResultArray();
        
        log_message('info', 'Service Staff Submissions Without Schedules - Staff ID: ' . $staffId . ' | Count: ' . count($results));
        if (count($results) > 0) {
            log_message('debug', 'Service Staff Submissions Without Schedules - Results: ' . json_encode($results));
        }
        
        // Format these submissions as "virtual" schedule entries
        $virtualSchedules = [];
        foreach ($results as $row) {
            // Use approval date if present, otherwise fall back to submission created date
            $createdDate = null;
            if (!empty($row['approved_at'])) {
                $createdDate = substr($row['approved_at'], 0, 10);
            } else {
                $createdDate = substr($row['created_at'], 0, 10);
            }
            
            // Get service staff name
            $staffName = null;
            if (!empty($row['service_staff_id'])) {
                $staff = $this->userModel->find($row['service_staff_id']);
                $staffName = $staff['full_name'] ?? null;
            }
            
            // Get priority_level from submission data
            $submissionDataModel = new \App\Models\FormSubmissionDataModel();
            $priorityLevel = $submissionDataModel->getFieldValue($row['submission_id'], 'priority_level');
            
            // Set default priority_level if none exists
            if (empty($priorityLevel)) {
                $priorityLevel = 'low'; // Default priority changed to low
                // Optionally save the default priority
                $submissionDataModel->setFieldValue($row['submission_id'], 'priority_level', $priorityLevel);
            }
            
            $virtualSchedules[] = [
                'id' => 'sub-' . $row['submission_id'], // Prefix with 'sub-' to distinguish from real schedules
                'submission_id' => $row['submission_id'],
                'form_id' => $row['form_id'],
                'panel_name' => $row['panel_name'],
                'submission_status' => $row['submission_status'],
                'form_code' => $row['form_code'],
                'form_description' => $row['form_description'],
                'requestor_name' => $row['requestor_name'],
                'requestor_department' => $row['requestor_department_name'] ?? null,
                'submission_created_at' => $row['submission_created_at'] ?? $row['created_at'] ?? null,
                'scheduled_date' => $createdDate,
                'scheduled_time' => '09:00:00', // Default time
                'duration_minutes' => 60,
                'location' => '',
                'notes' => 'Pending schedule assignment',
                'status' => $row['submission_status'], // Use actual submission status, not 'pending'
                'assigned_staff_id' => $row['service_staff_id'],
                'assigned_staff_name' => $staffName,
                'priority' => $row['priority'] ?? 0,
                'eta_days' => null,
                'estimated_date' => null,
                'priority_level' => $priorityLevel, // Now properly set
                'requestor_department_id' => $row['department_id']
            ];
        }
        
        return $virtualSchedules;
    }

    /**
     * Get requestor's submissions that don't have schedule entries yet
     * Shows all statuses so requestors can track their submissions on the calendar
     */
    private function getRequestorSubmissionsWithoutSchedules($userId, $existingSubmissionIds = [])
    {
        $db = \Config\Database::connect();
        
        // Find submissions by this requestor that don't have schedules
        $builder = $db->table('form_submissions fs');
        $builder->select('fs.id as submission_id, fs.form_id, fs.panel_name, fs.status as submission_status,
                          fs.created_at, fs.priority,
                          f.code as form_code, f.description as form_description')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->where('fs.submitted_by', $userId)
            ->where('NOT EXISTS (SELECT 1 FROM schedules s WHERE s.submission_id = fs.id)', null, false)
            ->whereIn('fs.status', ['submitted', 'approved', 'pending_service', 'completed']);
        
        // Exclude submissions that already have schedules (passed from parent)
        if (!empty($existingSubmissionIds)) {
            $existingWithSchedules = $db->table('schedules')->select('submission_id')->whereIn('submission_id', $existingSubmissionIds)->get()->getResultArray();
            $idsWithSchedules = array_column($existingWithSchedules, 'submission_id');
            if (!empty($idsWithSchedules)) {
                $builder->whereNotIn('fs.id', $idsWithSchedules);
            }
        }
        
        $builder->orderBy('fs.created_at', 'DESC');
        
        $results = $builder->get()->getResultArray();
        
        // Format as virtual schedule entries
        $virtualSchedules = [];
        foreach ($results as $row) {
            $createdDate = substr($row['created_at'], 0, 10);
            
            $virtualSchedules[] = [
                'id' => 'sub-' . $row['submission_id'],
                'submission_id' => $row['submission_id'],
                'submitted_by' => $row['submitted_by'] ?? null,
                'submitted_by' => $userId,
                'form_id' => $row['form_id'],
                'panel_name' => $row['panel_name'],
                'submission_status' => $row['submission_status'],
                'form_code' => $row['form_code'],
                'form_description' => $row['form_description'],
                'requestor_name' => 'You',
                'scheduled_date' => $createdDate,
                'scheduled_time' => '09:00:00',
                'duration_minutes' => 60,
                'location' => '',
                'notes' => 'Pending schedule assignment',
                'status' => $row['submission_status'],
                'assigned_staff_id' => null,
                'assigned_staff_name' => null,
                'priority' => $row['priority'] ?? 0,
                'eta_days' => null,
                'estimated_date' => null,
                'priority_level' => null
            ];
        }
        
        return $virtualSchedules;
    }

    /**
     * Get submissions for approver that don't have schedule entries yet
     */
    private function getApproverSubmissionsWithoutSchedules($userId, $departmentId = null, $isGlobalAdmin = false, $existingSubmissionIds = [])
    {
        $db = \Config\Database::connect();
        
        // Find submissions assigned to this approver that don't have schedules
        $builder = $db->table('form_submissions fs');
        $builder->select('fs.id as submission_id, fs.form_id, fs.panel_name, fs.status as submission_status,
                          fs.created_at, fs.created_at as submission_created_at, fs.priority,
                          f.code as form_code, f.description as form_description,
                          u.full_name as requestor_name, u.department_id as requestor_department_id, d.description as requestor_department_name')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->join('form_signatories fsig', 'fsig.form_id = fs.form_id AND fsig.user_id = ' . $userId, 'inner')
            ->where('NOT EXISTS (SELECT 1 FROM schedules s WHERE s.submission_id = fs.id)', null, false)
            ->groupStart()
                ->whereIn('fs.status', ['submitted','approved','completed']) // Pending, approved, or completed
                ->orWhere('fs.approver_id', $userId) // Already approved by this user
            ->groupEnd();
        
        // Filter by department if not global admin
        if (!$isGlobalAdmin && $departmentId) {
            $builder->where('u.department_id', $departmentId);
        }
        
        // Exclude submissions that already have schedules (passed from parent)
        if (!empty($existingSubmissionIds)) {
            $builder->whereNotIn('fs.id', $existingSubmissionIds);
        }
        
        $builder->orderBy('fs.created_at', 'DESC');
        
        $results = $builder->get()->getResultArray();
        
        // Format as virtual schedule entries
        $virtualSchedules = [];
        foreach ($results as $row) {
            $createdDate = substr($row['created_at'], 0, 10);
            
            $virtualSchedules[] = [
                'id' => 'sub-' . $row['submission_id'],
                'submission_id' => $row['submission_id'],
                'form_id' => $row['form_id'],
                'panel_name' => $row['panel_name'],
                'submission_status' => $row['submission_status'],
                'form_code' => $row['form_code'],
                'form_description' => $row['form_description'],
                'requestor_name' => $row['requestor_name'],
                'requestor_department' => $row['requestor_department_name'] ?? null,
                'submission_created_at' => $row['submission_created_at'] ?? $row['created_at'] ?? null,
                'scheduled_date' => $createdDate,
                'scheduled_time' => '09:00:00',
                'duration_minutes' => 60,
                'location' => '',
                'notes' => 'Pending schedule assignment',
                // Use the actual submission status so approvers see correct state
                'status' => $row['submission_status'] ?? 'pending',
                'assigned_staff_id' => null,
                'assigned_staff_name' => null,
                'priority' => $row['priority'] ?? 0,
                'eta_days' => null,
                'estimated_date' => null,
                'priority_level' => null,
                'requestor_department_id' => $row['department_id']
            ];
        }
        
        return $virtualSchedules;
    }

    /**
     * Admin view: list prioritized schedules
     */
    public function priorities()
    {
        $userType = session()->get('user_type');
        if ($userType !== 'admin' && $userType !== 'superuser') {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized');
        }

        $data['title'] = 'Prioritized Schedules';
        $data['schedules'] = $this->scheduleModel->getPrioritizedSchedules();

        return view('schedule/priorities', $data);
    }

    /**
     * Bulk unmark priorities (admin)
     */
    public function bulkUnmarkPriorities()
    {
        $userType = session()->get('user_type');
        if ($userType !== 'admin' && $userType !== 'superuser') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $ids = $this->request->getPost('ids'); // expected CSV or array
        if (is_string($ids)) {
            $ids = array_filter(array_map('intval', explode(',', $ids)));
        }

        if (empty($ids) || !is_array($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No schedules selected', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        foreach ($ids as $id) {
            $this->scheduleModel->update($id, ['priority' => 0]);
        }

    return $this->response->setJSON(['success' => true, 'message' => 'Priorities cleared', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
    }

    public function markComplete($id)
    {
        $schedule = $this->scheduleModel->find($id);
        if (!$schedule) {
            return $this->response->setJSON(['success' => false, 'message' => 'Schedule not found', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        // Ensure schedule has an assigned staff if the submission has one
        $submission = null;
        if (empty($schedule['assigned_staff_id']) && !empty($schedule['submission_id'])) {
            try {
                $submission = $this->submissionModel->find($schedule['submission_id']);
                if (!empty($submission['service_staff_id'])) {
                    $data['assigned_staff_id'] = $submission['service_staff_id'];
                }
            } catch (\Exception $e) {
                log_message('error', 'Failed to load submission for schedule #' . ($schedule['id'] ?? 'unknown') . ': ' . $e->getMessage());
            }
        } else {
            // Load submission when present for later updates
            if (!empty($schedule['submission_id'])) {
                try {
                    $submission = $this->submissionModel->find($schedule['submission_id']);
                } catch (\Exception $e) {
                    log_message('error', 'Failed to load submission for schedule #' . ($schedule['id'] ?? 'unknown') . ': ' . $e->getMessage());
                }
            }
        }

        $completionNotes = $this->request->getPost('completion_notes');
        
        $updateData = [
            'status' => 'completed',
            'completion_notes' => $completionNotes
        ];

        if ($this->scheduleModel->update($id, $updateData)) {
            // Update the related submission (log for diagnostics)
            log_message('info', 'Schedule::markComplete - calling markAsServiced for submission ' . ($schedule['submission_id'] ?? 'null') . ' by user ' . session()->get('user_id'));
            $this->submissionModel->markAsServiced(
                $schedule['submission_id'],
                session()->get('user_id'),
                $completionNotes
            );

            // Notification is created by FormSubmissionModel::markAsServiced

            return $this->response->setJSON(['success' => true, 'message' => 'Service completed successfully', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

    return $this->response->setJSON(['success' => false, 'message' => 'Failed to mark service as completed', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
    }

    /**
     * Toggle priority flag for a schedule (admin only)
     */
    public function togglePriority($id)
    {
        $userType = session()->get('user_type');
        if ($userType !== 'admin' && $userType !== 'superuser') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $schedule = $this->scheduleModel->find($id);
        if (!$schedule) {
            return $this->response->setJSON(['success' => false, 'message' => 'Schedule not found', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $result = $this->scheduleModel->togglePriority($id);
        if ($result) {
            return $this->response->setJSON(['success' => true, 'priority' => (int)!empty($schedule['priority']) ? 0 : 1, 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to toggle priority', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
    }

    /**
     * AJAX-only: update priority level and/or scheduled date/time and compute ETA without running full validation
     */
    public function updatePriority($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        // Handle virtual events (submissions without schedules) - they have IDs like "sub-123" or "staff-123"
        if (is_string($id) && (strpos($id, 'sub-') === 0 || strpos($id, 'staff-') === 0)) {
            $submissionId = (int)str_replace(['sub-', 'staff-'], '', $id);
            
            // Get submission details
            $submission = $this->submissionModel->find($submissionId);
            if (!$submission) {
                return $this->response->setJSON(['success' => false, 'message' => 'Submission not found', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
            }
            
            // Get POST data
            $priorityLevel = $this->request->getPost('priority_level') ?: null;
            $newScheduledDate = $this->request->getPost('scheduled_date') ?: date('Y-m-d');
            $newScheduledTime = $this->request->getPost('scheduled_time') ?: '09:00:00';
            $isManualSchedule = $this->request->getPost('is_manual_schedule') ?: '0';
            
            // Calculate ETA based on priority level
            $etaDays = null;
            $estimatedDate = null;
            
            if ($isManualSchedule === '1' || $isManualSchedule === 1) {
                $estimatedDate = $newScheduledDate;
                $etaDays = 0;
            } elseif ($priorityLevel) {
                if ($priorityLevel === 'high') {
                    $etaDays = 3;
                    $estimatedDate = $this->addBusinessDays($newScheduledDate, 3);
                } elseif ($priorityLevel === 'medium') {
                    $etaDays = 5;
                    $estimatedDate = $this->addBusinessDays($newScheduledDate, 5);
                } elseif ($priorityLevel === 'low') {
                    $etaDays = 7;
                    $estimatedDate = date('Y-m-d', strtotime($newScheduledDate . ' +7 days'));
                }
            }
            
            // Prepare schedule data (will insert or update existing)
            $scheduleData = [
                'submission_id' => $submissionId,
                'scheduled_date' => $newScheduledDate,
                'scheduled_time' => $newScheduledTime,
                'duration_minutes' => 60,
                'assigned_staff_id' => $submission['service_staff_id'] ?? null,
                'location' => '',
                'notes' => 'Created from calendar',
                'status' => 'confirmed',
                'priority_level' => $priorityLevel,
                'eta_days' => $etaDays,
                'estimated_date' => $estimatedDate,
                'is_manual_schedule' => $isManualSchedule
            ];
            // Check if a schedule already exists for this submission — update it instead of inserting to avoid duplicates
            $existingSched = $this->scheduleModel->where('submission_id', $submissionId)->first();
            if ($existingSched) {
                // Update existing schedule
                $updateId = $existingSched['id'];
                $updateSuccess = $this->scheduleModel->update($updateId, $scheduleData);
                if ($updateSuccess) {
                    // Ensure submission status is at least pending_service
                    $currentStatus = $submission['status'] ?? null;
                    if (!in_array($currentStatus, ['pending_service', 'completed'])) {
                        try {
                            $this->submissionModel->update($submissionId, ['status' => 'pending_service']);
                        } catch (\Exception $e) {
                            log_message('error', 'Failed to update submission status for ID ' . $submissionId . ': ' . $e->getMessage());
                        }
                    }

                    return $this->response->setJSON([
                        'success' => true,
                        'message' => 'Schedule updated successfully',
                        'schedule_id' => $updateId,
                        'estimated_date' => $estimatedDate,
                        'eta_days' => $etaDays,
                        'scheduled_date' => $newScheduledDate,
                        'scheduled_time' => $newScheduledTime,
                        'is_manual_schedule' => $isManualSchedule,
                        'csrf_name' => csrf_token(),
                        'csrf_hash' => csrf_hash()
                    ]);
                } else {
                    return $this->response->setJSON(['success' => false, 'message' => 'Failed to update existing schedule', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
                }
            }

            // No existing schedule found — create a new schedule entry
            $newScheduleId = $this->scheduleModel->insert($scheduleData);

            if ($newScheduleId) {
                // If submission isn't already pending service or completed, mark it as pending_service
                $currentStatus = $submission['status'] ?? null;
                if (!in_array($currentStatus, ['pending_service', 'completed'])) {
                    try {
                        $this->submissionModel->update($submissionId, ['status' => 'pending_service']);
                    } catch (\Exception $e) {
                        // Log but do not fail the schedule creation if submission update fails
                        log_message('error', 'Failed to update submission status for ID ' . $submissionId . ': ' . $e->getMessage());
                    }
                }
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Schedule created successfully',
                    'schedule_id' => $newScheduleId,
                    'estimated_date' => $estimatedDate,
                    'eta_days' => $etaDays,
                    'scheduled_date' => $newScheduledDate,
                    'scheduled_time' => $newScheduledTime,
                    'is_manual_schedule' => $isManualSchedule,
                    'csrf_name' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ]);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Failed to create schedule', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
            }
        }

        $schedule = $this->scheduleModel->find($id);
        if (!$schedule) {
            return $this->response->setJSON(['success' => false, 'message' => 'Schedule not found', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $priorityLevel = $this->request->getPost('priority_level') ?: null;
        $newScheduledDate = $this->request->getPost('scheduled_date') ?: null;
        $newScheduledTime = $this->request->getPost('scheduled_time') ?: null;
        $isManualSchedule = $this->request->getPost('is_manual_schedule') ?: '0';
        
        // Use the new scheduled date if provided, otherwise use existing
        $scheduledDate = $newScheduledDate ?: $schedule['scheduled_date'];

        $data = [];
        
        // Update scheduled date and time if provided
        if ($newScheduledDate) {
            $data['scheduled_date'] = $newScheduledDate;
        }
        if ($newScheduledTime) {
            $data['scheduled_time'] = $newScheduledTime;
        }
        
        // Save the manual schedule flag
        $data['is_manual_schedule'] = $isManualSchedule;
        
        // Handle manual schedule: target completion = scheduled date
        if ($isManualSchedule === '1' || $isManualSchedule === 1) {
            $data['estimated_date'] = $scheduledDate;
            $data['eta_days'] = 0; // Same day
            if ($priorityLevel) {
                $data['priority_level'] = $priorityLevel;
            }
        }
        // Compute ETA based on priority level for auto-scheduled items
        elseif ($priorityLevel) {
            $scheduledDateForEta = $scheduledDate;
            $etaDays = null; $estimatedDate = null;
            if ($priorityLevel === 'low') {
                $etaDays = 7;
                $estimatedDate = date('Y-m-d', strtotime($scheduledDateForEta . ' +7 days'));
            } elseif ($priorityLevel === 'medium') {
                $etaDays = 5;
                $estimatedDate = $this->addBusinessDays($scheduledDateForEta, 5);
            } elseif ($priorityLevel === 'high') {
                $etaDays = 3;
                $estimatedDate = $this->addBusinessDays($scheduledDateForEta, 3);
            }
            if ($etaDays && $estimatedDate) {
                $data['eta_days'] = $etaDays;
                $data['priority_level'] = $priorityLevel;
                $data['estimated_date'] = $estimatedDate;
            }
        } else {
            // Clear priority if empty and not manual
            $data['eta_days'] = null;
            $data['priority_level'] = null;
            $data['estimated_date'] = null;
        }

        $updated = $this->scheduleModel->update($id, $data);
        if ($updated) {
            // Ensure the associated submission is marked as pending_service when schedule is updated
            try {
                $submissionId = $schedule['submission_id'] ?? null;
                if ($submissionId) {
                    $currentStatus = $this->submissionModel->find($submissionId)['status'] ?? null;
                    if (!in_array($currentStatus, ['pending_service', 'completed'])) {
                        $this->submissionModel->update($submissionId, ['status' => 'pending_service']);
                    }
                }
            } catch (\Exception $e) {
                log_message('error', 'Failed to update submission status after schedule update for submission ID ' . ($submissionId ?? 'unknown') . ': ' . $e->getMessage());
            }
            return $this->response->setJSON([
                'success' => true, 
                'estimated_date' => $data['estimated_date'] ?? null, 
                'eta_days' => $data['eta_days'] ?? null,
                'scheduled_date' => $data['scheduled_date'] ?? $schedule['scheduled_date'],
                'scheduled_time' => $data['scheduled_time'] ?? $schedule['scheduled_time'],
                'is_manual_schedule' => $data['is_manual_schedule'] ?? 0,
                'csrf_name' => csrf_token(), 
                'csrf_hash' => csrf_hash()
            ]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to update', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
    }

    /**
     * AJAX: Update priority_level field stored in submission data (not schedule) and compute estimated completion date for calendar event.
     * This provides an alternative when a schedule row doesn't yet exist or user wants submission-centric ETA.
     */
    public function updateSubmissionPriority($submissionId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $submission = $this->submissionModel->find($submissionId);
        if (!$submission) {
            return $this->response->setJSON(['success' => false, 'message' => 'Submission not found', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $priorityLevel = $this->request->getPost('priority_level');
        $allowed = ['high','medium','low'];
        if ($priorityLevel && !in_array($priorityLevel, $allowed, true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid priority level', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        // Upsert field in submission data
        $submissionDataModel = new \App\Models\FormSubmissionDataModel();
        try {
            if ($priorityLevel) {
                $result = $submissionDataModel->setFieldValue($submissionId, 'priority_level', $priorityLevel);
                log_message('info', "Priority set for submission {$submissionId}: {$priorityLevel}, result: " . ($result ? 'success' : 'failed'));
            } else {
                // Clear priority by setting empty value
                $result = $submissionDataModel->setFieldValue($submissionId, 'priority_level', '');
                log_message('info', "Priority cleared for submission {$submissionId}, result: " . ($result ? 'success' : 'failed'));
            }
            
            if (!$result) {
                return $this->response->setJSON([
                    'success' => false, 
                    'message' => 'Failed to update priority in database',
                    'csrf_name' => csrf_token(), 
                    'csrf_hash' => csrf_hash()
                ]);
            }
            // If priority was set, ensure submission status reflects pending service so staff see it
            if ($priorityLevel) {
                try {
                    $currentStatus = $submission['status'] ?? null;
                    if (!in_array($currentStatus, ['pending_service', 'completed'])) {
                        $this->submissionModel->update($submissionId, ['status' => 'pending_service']);
                    }
                } catch (\Exception $e) {
                    log_message('error', 'Failed to update submission status after priority change for submission ID ' . $submissionId . ': ' . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            log_message('error', "Error updating priority for submission {$submissionId}: " . $e->getMessage());
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Database error: ' . $e->getMessage(),
                'csrf_name' => csrf_token(), 
                'csrf_hash' => csrf_hash()
            ]);
        }

        // Compute ETA off submission created_at using new mapping
        $etaDays = null; $estimatedDate = null;
        if ($priorityLevel) {
            if ($priorityLevel === 'low') {
                $etaDays = 7;
                $estimatedDate = date('Y-m-d', strtotime($submission['created_at'] . ' +7 days'));
            } elseif ($priorityLevel === 'medium') {
                $etaDays = 5;
                $estimatedDate = $this->addBusinessDays(substr($submission['created_at'],0,10), 5);
            } elseif ($priorityLevel === 'high') {
                $etaDays = 3;
                $estimatedDate = $this->addBusinessDays(substr($submission['created_at'],0,10), 3);
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'priority_level' => $priorityLevel,
            'estimated_date' => $estimatedDate,
            'eta_days' => $etaDays,
            'csrf_name' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ]);
    }

    /**
     * Get submissions directly assigned to service staff (not through schedules)
     * This is a fallback method to ensure service staff see their assignments
     */
    private function getDirectServiceStaffAssignments($staffId)
    {
        $db = \Config\Database::connect();
        
        // Find submissions where service_staff_id = $staffId
        $builder = $db->table('form_submissions fs');
        $builder->select('fs.id as submission_id, fs.form_id, fs.panel_name, fs.status as submission_status,
                          fs.created_at, fs.priority, fs.service_staff_id,
                          f.code as form_code, f.description as form_description,
                          u.full_name as requestor_name, fsd.field_value as priority_level')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('form_submission_data fsd', 'fsd.submission_id = fs.id AND fsd.field_name = "priority_level"', 'left')
            ->where('fs.service_staff_id', $staffId)
            ->whereIn('fs.status', ['approved', 'pending_service', 'submitted']) // Include all relevant statuses
            ->orderBy('fs.created_at', 'DESC');
        
        $results = $builder->get()->getResultArray();
        
        // Format these submissions as "virtual" schedule entries for calendar display
        $virtualSchedules = [];
        foreach ($results as $row) {
            // Use submission created date as the scheduled date
            $createdDate = substr($row['created_at'], 0, 10);
            
            $virtualSchedules[] = [
                'id' => 'staff-' . $row['submission_id'], // Prefix with 'staff-' to distinguish
                'submission_id' => $row['submission_id'],
                'form_id' => $row['form_id'],
                'panel_name' => $row['panel_name'],
                'submission_status' => $row['submission_status'],
                'form_code' => $row['form_code'],
                'form_description' => $row['form_description'],
                'requestor_name' => $row['requestor_name'],
                'scheduled_date' => $createdDate,
                'scheduled_time' => '10:00:00', // Default time for staff assignments
                'duration_minutes' => 60,
                'location' => '',
                'notes' => 'Service assignment - schedule pending',
                'status' => 'pending',
                'assigned_staff_id' => $row['service_staff_id'],
                'assigned_staff_name' => 'You',
                'priority' => $row['priority'] ?? 0,
                'eta_days' => null,
                'estimated_date' => null,
                'priority_level' => $row['priority_level'] ?? 'medium'
            ];
        }
        
        return $virtualSchedules;
    }
    
    /**
     * Get submissions without schedules for a specific department
     * Filters by FORM's department to match getDepartmentSchedules logic
     */
    private function getDepartmentSubmissionsWithoutSchedules($departmentId)
    {
        $db = \Config\Database::connect();
        
        // Find submissions for forms that belong to the specified department and don't have schedules
        $builder = $db->table('form_submissions fs');
        $builder->select('fs.id as submission_id, fs.form_id, fs.panel_name, fs.status as submission_status,
                          fs.created_at, fs.priority,
                          f.code as form_code, f.description as form_description, f.department_id as form_department_id,
                          u.full_name as requestor_name, u.department_id as requestor_department_id')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->where('f.department_id', $departmentId)  // FIXED: Filter by form's department, not requestor's
            ->where('NOT EXISTS (SELECT 1 FROM schedules s WHERE s.submission_id = fs.id)', null, false)
            ->whereIn('fs.status', ['submitted', 'approved', 'pending_service'])
            ->orderBy('fs.created_at', 'DESC');
        
        $results = $builder->get()->getResultArray();
        
        // Format as virtual schedule entries
        $virtualSchedules = [];
        foreach ($results as $row) {
            $createdDate = substr($row['created_at'], 0, 10);
            
            $virtualSchedules[] = [
                'id' => 'sub-' . $row['submission_id'],
                'submission_id' => $row['submission_id'],
                'form_id' => $row['form_id'],
                'panel_name' => $row['panel_name'],
                'submission_status' => $row['submission_status'],
                'form_code' => $row['form_code'],
                'form_description' => $row['form_description'],
                'requestor_name' => $row['requestor_name'],
                'scheduled_date' => $createdDate,
                'scheduled_time' => '09:00:00',
                'duration_minutes' => 60,
                'location' => '',
                'notes' => 'Pending schedule assignment',
                'status' => 'pending',
                'assigned_staff_id' => null,
                'assigned_staff_name' => null,
                'priority' => $row['priority'] ?? 0,
                'eta_days' => null,
                'estimated_date' => null,
                'priority_level' => null,
                'requestor_department_id' => $row['department_id']
            ];
        }
        
        return $virtualSchedules;
    }
}
