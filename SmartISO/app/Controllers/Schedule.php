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
        
        $data['title'] = 'Service Schedules';
        
        // Admin and superuser can see all schedules AND submissions without schedules
        if (in_array($userType, ['admin', 'superuser'])) {
            $schedules = $this->scheduleModel->getSchedulesWithDetails();
            
            // Also get submissions that don't have schedule entries yet
            $submissionsWithoutSchedules = $this->getSubmissionsWithoutSchedules();
            // Merge them into the schedules array
            $schedules = array_merge($schedules, $submissionsWithoutSchedules);
        }
        // Service staff sees schedules assigned to them
        elseif ($userType === 'service_staff') {
            $schedules = $this->scheduleModel->getStaffSchedules($userId);
            
            // Also get submissions assigned to this service staff that don't have schedules yet
            $submissionsWithoutSchedules = $this->getServiceStaffSubmissionsWithoutSchedules($userId);
            // Merge them into the schedules array
            $schedules = array_merge($schedules, $submissionsWithoutSchedules);
        }
        // Requestor sees schedules for their submissions
        elseif ($userType === 'requestor') {
            $submissions = $this->submissionModel->where('submitted_by', $userId)->findAll();
            $submissionIds = array_column($submissions, 'id');
            if (!empty($submissionIds)) {
                // Use the new method to get schedules with full details
                $schedules = $this->scheduleModel->getSchedulesBySubmissions($submissionIds);
                
                // If no schedules exist, show pending submissions as placeholder events
                if (empty($schedules)) {
                    $pendingSubs = $this->submissionModel->whereIn('id', $submissionIds)
                        ->whereIn('status', ['submitted', 'approved'])
                        ->orderBy('created_at', 'ASC')
                        ->findAll();

                    if (!empty($pendingSubs)) {
                        $formModel = new \App\Models\FormModel();
                        $schedules = [];
                        foreach ($pendingSubs as $ps) {
                            $form = $formModel->find($ps['form_id']);
                            $schedules[] = [
                                'id' => 'sub-' . $ps['id'],
                                'priority' => 0,
                                'form_code' => $form['code'] ?? null,
                                'panel_name' => $ps['panel_name'] ?? null,
                                'scheduled_date' => isset($ps['created_at']) ? substr($ps['created_at'], 0, 10) : date('Y-m-d'),
                                'scheduled_time' => '09:00:00',
                                'notes' => null,
                                'status' => $ps['status'] ?? 'submitted'
                            ];
                        }
                    }
                }
            } else {
                $schedules = [];
            }
        }
        // Approving authority sees schedules for submissions they approved
        elseif ($userType === 'approving_authority') {
            // Get submissions approved by this user
            $submissions = $this->submissionModel->where('approver_id', $userId)->findAll();
            $submissionIds = array_column($submissions, 'id');
            if (!empty($submissionIds)) {
                // Use the new method to get schedules with full details
                $schedules = $this->scheduleModel->getSchedulesBySubmissions($submissionIds);
            } else {
                $schedules = [];
            }
        }
        else {
            $schedules = $this->scheduleModel->getSchedulesWithDetails();
        }

        // If none found, fallback to pending schedules for next 30 days (for backward compatibility)
        if (empty($schedules)) {
            $start = date('Y-m-d');
            $end = date('Y-m-d', strtotime('+30 days'));
            $schedules = $this->scheduleModel->getPendingSchedules($start, $end);
        }

        $calendarEvents = [];
        foreach ($schedules as $schedule) {
            $title = ($schedule['priority'] ?? 0) ? '★ ' : '';
            $title .= $schedule['form_code'] ?? ($schedule['panel_name'] ?? 'Service');

            // Use submission_status if available, otherwise fall back to schedule status
            $status = $schedule['submission_status'] ?? $schedule['status'] ?? 'pending';

            $calendarEvents[] = [
                'id' => $schedule['id'],
                'title' => $title,
                'start' => $schedule['scheduled_date'] . 'T' . $schedule['scheduled_time'],
                'description' => $schedule['notes'] ?? null,
                'status' => $status,
                'priority' => (int)($schedule['priority'] ?? 0),
                'estimated_date' => $schedule['estimated_date'] ?? null,
                'eta_days' => isset($schedule['eta_days']) ? (int)$schedule['eta_days'] : null,
                'priority_level' => $schedule['priority_level'] ?? null,
                'scheduled_time' => $schedule['scheduled_time'] ?? null
            ];
        }

        $data['events'] = json_encode($calendarEvents);
        $data['events_count'] = count($calendarEvents);

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

        $data = [
            'submission_id'      => $this->request->getPost('submission_id'),
            'scheduled_date'     => $this->request->getPost('scheduled_date'),
            'scheduled_time'     => $this->request->getPost('scheduled_time'),
            'duration_minutes'   => $this->request->getPost('duration_minutes') ?: 60,
            'assigned_staff_id'  => $this->request->getPost('assigned_staff_id'),
            'location'          => $this->request->getPost('location'),
            'notes'             => $this->request->getPost('notes'),
            'status'            => 'confirmed'
        ];

        // Compute ETA from priority_level if provided
        if ($priorityLevel) {
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

        $data = [
            'scheduled_date'     => $this->request->getPost('scheduled_date') ?: ($jsonBody['scheduled_date'] ?? $schedule['scheduled_date']),
            'scheduled_time'     => $this->request->getPost('scheduled_time'),
            'duration_minutes'   => $this->request->getPost('duration_minutes') ?: 60,
            'assigned_staff_id'  => $this->request->getPost('assigned_staff_id'),
            'location'          => $this->request->getPost('location'),
            'notes'             => $this->request->getPost('notes'),
            'status'            => $this->request->getPost('status')
        ];

        if ($priorityLevel) {
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
        
        // Admin and superuser can see all schedules AND submissions without schedules
        if (in_array($userType, ['admin', 'superuser'])) {
            $schedules = $this->scheduleModel->getSchedulesWithDetails();
            
            // Also get submissions that don't have schedule entries yet
            $submissionsWithoutSchedules = $this->getSubmissionsWithoutSchedules();
            // Merge them into the schedules array
            $schedules = array_merge($schedules, $submissionsWithoutSchedules);
        }
        // Service staff sees schedules assigned to them
        elseif ($userType === 'service_staff') {
            $schedules = $this->scheduleModel->getStaffSchedules($userId);
            
            // Also get submissions assigned to this service staff that don't have schedules yet
            $submissionsWithoutSchedules = $this->getServiceStaffSubmissionsWithoutSchedules($userId);
            // Merge them into the schedules array
            $schedules = array_merge($schedules, $submissionsWithoutSchedules);
        }
        // Requestor sees schedules for their submissions
        elseif ($userType === 'requestor') {
            $submissions = $this->submissionModel->where('submitted_by', $userId)->findAll();
            $submissionIds = array_column($submissions, 'id');
            if (!empty($submissionIds)) {
                // Use the new method to get schedules with full details
                $schedules = $this->scheduleModel->getSchedulesBySubmissions($submissionIds);
            } else {
                $schedules = [];
            }
        }
        // Approving authority sees schedules for submissions they approved
        elseif ($userType === 'approving_authority') {
            $submissions = $this->submissionModel->where('approver_id', $userId)->findAll();
            $submissionIds = array_column($submissions, 'id');
            if (!empty($submissionIds)) {
                // Use the new method to get schedules with full details
                $schedules = $this->scheduleModel->getSchedulesBySubmissions($submissionIds);
            } else {
                $schedules = [];
            }
        }
        else {
            $schedules = $this->scheduleModel->getSchedulesWithDetails();
        }

        // If no schedules found, try to fetch pending schedules for the next 30 days as a fallback
        if (empty($schedules)) {
            $start = date('Y-m-d');
            $end = date('Y-m-d', strtotime('+30 days'));
            $schedules = $this->scheduleModel->getPendingSchedules($start, $end);
        }
        
        // Format schedules for calendar display
        $calendarEvents = [];
        foreach ($schedules as $schedule) {
            $title = ($schedule['priority'] ?? 0) ? '★ ' : '';
            $title .= $schedule['form_code'] ?? 'Service';

            // Use submission_status if available, otherwise fall back to schedule status
            $status = $schedule['submission_status'] ?? $schedule['status'] ?? 'pending';

            $calendarEvents[] = [
                'id' => $schedule['id'],
                'title' => $title,
                'start' => $schedule['scheduled_date'] . 'T' . $schedule['scheduled_time'],
                'description' => $schedule['notes'],
                'status' => $status,
                'priority' => (int)($schedule['priority'] ?? 0),
                'estimated_date' => $schedule['estimated_date'] ?? null,
                'eta_days' => isset($schedule['eta_days']) ? (int)$schedule['eta_days'] : null,
                'priority_level' => $schedule['priority_level'] ?? null
            ];
        }
        
    $data['events'] = json_encode($calendarEvents);
    $data['events_count'] = count($calendarEvents);
        
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
                          fs.created_at, fs.priority,
                          f.code as form_code, f.description as form_description,
                          u.full_name as requestor_name')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->where('NOT EXISTS (SELECT 1 FROM schedules s WHERE s.submission_id = fs.id)', null, false)
            ->whereIn('fs.status', ['submitted', 'approved', 'pending_service']) // Only show active submissions
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
                'form_id' => $row['form_id'],
                'panel_name' => $row['panel_name'],
                'submission_status' => $row['submission_status'],
                'form_code' => $row['form_code'],
                'form_description' => $row['form_description'],
                'requestor_name' => $row['requestor_name'],
                'scheduled_date' => $createdDate,
                'scheduled_time' => '09:00:00', // Default time
                'duration_minutes' => 60,
                'location' => '',
                'notes' => 'Pending schedule assignment',
                'status' => 'pending',
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
     * Get submissions assigned to a service staff member that don't have schedule entries yet
     * This ensures service staff can see ALL submissions assigned to them
     */
    private function getServiceStaffSubmissionsWithoutSchedules($staffId)
    {
        $db = \Config\Database::connect();
        
        // Find submissions assigned to this service staff that don't have a corresponding schedule entry
        $builder = $db->table('form_submissions fs');
        $builder->select('fs.id as submission_id, fs.form_id, fs.panel_name, fs.status as submission_status,
                          fs.created_at, fs.priority, fs.service_staff_id,
                          f.code as form_code, f.description as form_description,
                          u.full_name as requestor_name')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->where('fs.service_staff_id', $staffId)
            ->where('NOT EXISTS (SELECT 1 FROM schedules s WHERE s.submission_id = fs.id)', null, false)
            ->whereIn('fs.status', ['approved', 'pending_service']) // Only show submissions assigned to service staff
            ->orderBy('fs.created_at', 'DESC');
        
        $results = $builder->get()->getResultArray();
        
        // Format these submissions as "virtual" schedule entries
        $virtualSchedules = [];
        foreach ($results as $row) {
            // Use submission created date as the scheduled date
            $createdDate = substr($row['created_at'], 0, 10);
            
            // Get service staff name
            $staffName = null;
            if (!empty($row['service_staff_id'])) {
                $staff = $this->userModel->find($row['service_staff_id']);
                $staffName = $staff['full_name'] ?? null;
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
                'scheduled_date' => $createdDate,
                'scheduled_time' => '09:00:00', // Default time
                'duration_minutes' => 60,
                'location' => '',
                'notes' => 'Pending schedule assignment',
                'status' => 'pending',
                'assigned_staff_id' => $row['service_staff_id'],
                'assigned_staff_name' => $staffName,
                'priority' => $row['priority'] ?? 0,
                'eta_days' => null,
                'estimated_date' => null,
                'priority_level' => null
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

        $completionNotes = $this->request->getPost('completion_notes');
        
        $updateData = [
            'status' => 'completed',
            'completion_notes' => $completionNotes
        ];

        if ($this->scheduleModel->update($id, $updateData)) {
            // Update the related submission
            $this->submissionModel->markAsServiced(
                $schedule['submission_id'],
                session()->get('user_id'),
                $completionNotes
            );

            // Get submission details for notification
            $submission = $this->submissionModel->find($schedule['submission_id']);
            
            // Create notification for requestor
            $this->notificationModel->createServiceCompletionNotification(
                $schedule['submission_id'],
                $submission['submitted_by']
            );

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
     * AJAX-only: update priority level and compute ETA without running full validation
     */
    public function updatePriority($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $schedule = $this->scheduleModel->find($id);
        if (!$schedule) {
            return $this->response->setJSON(['success' => false, 'message' => 'Schedule not found', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $priorityLevel = $this->request->getPost('priority_level') ?: null;
        $scheduledDate = $this->request->getPost('scheduled_date') ?: ($schedule['scheduled_date'] ?? null);

        $data = [];
        if ($priorityLevel) {
            $scheduledDateForEta = ($scheduledDate ?: $schedule['scheduled_date']);
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
            // Clear priority
            $data['eta_days'] = null;
            $data['priority_level'] = null;
            $data['estimated_date'] = null;
        }

        $updated = $this->scheduleModel->update($id, $data);
        if ($updated) {
            return $this->response->setJSON(['success' => true, 'estimated_date' => $data['estimated_date'] ?? null, 'eta_days' => $data['eta_days'] ?? null, 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to update priority', 'csrf_name' => csrf_token(), 'csrf_hash' => csrf_hash()]);
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
}
