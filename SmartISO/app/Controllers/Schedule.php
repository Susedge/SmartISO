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
        // Present the calendar view to all users, but scope events per role.
        // Requestors see only their submission schedules; service_staff see assigned schedules; admins and others see full schedules.
        if ($userType === 'service_staff') {
            $schedules = $this->scheduleModel->getStaffSchedules($userId);
        } elseif ($userType === 'requestor') {
            $submissions = $this->submissionModel->where('submitted_by', $userId)->findAll();
            $submissionIds = array_column($submissions, 'id');
            if (!empty($submissionIds)) {
                $schedules = $this->scheduleModel->whereIn('submission_id', $submissionIds)->findAll();
            } else {
                $schedules = [];
            }
        } else {
            // admin, approving_authority, superuser, and other privileged roles
            $schedules = $this->scheduleModel->getSchedulesWithDetails();
        }

        // If none found, fallback to pending schedules for next 30 days
        if (empty($schedules)) {
            $start = date('Y-m-d');
            $end = date('Y-m-d', strtotime('+30 days'));
            $schedules = $this->scheduleModel->getPendingSchedules($start, $end);
        }

        $calendarEvents = [];
        foreach ($schedules as $schedule) {
            $title = ($schedule['priority'] ?? 0) ? '★ ' : '';
            $title .= $schedule['form_code'] ?? ($schedule['panel_name'] ?? 'Service');

            $calendarEvents[] = [
                'id' => $schedule['id'],
                'title' => $title,
                'start' => $schedule['scheduled_date'] . 'T' . $schedule['scheduled_time'],
                'description' => $schedule['notes'] ?? null,
                'status' => $schedule['status'] ?? null,
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
            $map = ['high' => 3, 'medium' => 4, 'low' => 5];
            $etaDays = $map[$priorityLevel] ?? null;
            if ($etaDays) {
                $data['eta_days'] = $etaDays;
                $data['priority_level'] = $priorityLevel;
                $data['estimated_date'] = date('Y-m-d', strtotime($data['scheduled_date'] . " +{$etaDays} days"));
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
            $map = ['high' => 3, 'medium' => 4, 'low' => 5];
            $etaDays = $map[$priorityLevel] ?? null;
            if ($etaDays) {
                $data['eta_days'] = $etaDays;
                $data['priority_level'] = $priorityLevel;
                $scheduledDateForEta = $data['scheduled_date'] ?: $schedule['scheduled_date'];
                $data['estimated_date'] = date('Y-m-d', strtotime($scheduledDateForEta . " +{$etaDays} days"));
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
        
        if ($userType === 'service_staff') {
            $schedules = $this->scheduleModel->getStaffSchedules($userId);
        } else {
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

            $calendarEvents[] = [
                'id' => $schedule['id'],
                'title' => $title,
                'start' => $schedule['scheduled_date'] . 'T' . $schedule['scheduled_time'],
                'description' => $schedule['notes'],
                'status' => $schedule['status'],
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
            $map = ['high' => 3, 'medium' => 4, 'low' => 5];
            $etaDays = $map[$priorityLevel] ?? null;
            if ($etaDays) {
                $data['eta_days'] = $etaDays;
                $data['priority_level'] = $priorityLevel;
                $data['estimated_date'] = date('Y-m-d', strtotime(($scheduledDate ?: $schedule['scheduled_date']) . " +{$etaDays} days"));
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
}
