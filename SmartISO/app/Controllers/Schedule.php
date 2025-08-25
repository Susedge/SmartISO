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
        
        if ($userType === 'service_staff') {
            $data['schedules'] = $this->scheduleModel->getStaffSchedules($userId);
        } elseif ($userType === 'admin' || $userType === 'approving_authority') {
            $data['schedules'] = $this->scheduleModel->getSchedulesWithDetails();
        } else {
            // For requestors, show their schedules
            $submissions = $this->submissionModel->where('submitted_by', $userId)->findAll();
            $submissionIds = array_column($submissions, 'id');
            
            if (!empty($submissionIds)) {
                $data['schedules'] = $this->scheduleModel->whereIn('submission_id', $submissionIds)
                                                        ->findAll();
            } else {
                $data['schedules'] = [];
            }
        }
        
        return view('schedule/index', $data);
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

        $data = [
            'scheduled_date'     => $this->request->getPost('scheduled_date'),
            'scheduled_time'     => $this->request->getPost('scheduled_time'),
            'duration_minutes'   => $this->request->getPost('duration_minutes') ?: 60,
            'assigned_staff_id'  => $this->request->getPost('assigned_staff_id'),
            'location'          => $this->request->getPost('location'),
            'notes'             => $this->request->getPost('notes'),
            'status'            => $this->request->getPost('status')
        ];

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
                'priority' => (int)($schedule['priority'] ?? 0)
            ];
        }
        
        $data['events'] = json_encode($calendarEvents);
        
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
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $ids = $this->request->getPost('ids'); // expected CSV or array
        if (is_string($ids)) {
            $ids = array_filter(array_map('intval', explode(',', $ids)));
        }

        if (empty($ids) || !is_array($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No schedules selected']);
        }

        foreach ($ids as $id) {
            $this->scheduleModel->update($id, ['priority' => 0]);
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Priorities cleared']);
    }

    public function markComplete($id)
    {
        $schedule = $this->scheduleModel->find($id);
        if (!$schedule) {
            return $this->response->setJSON(['success' => false, 'message' => 'Schedule not found']);
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

            return $this->response->setJSON(['success' => true, 'message' => 'Service completed successfully']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to mark service as completed']);
    }

    /**
     * Toggle priority flag for a schedule (admin only)
     */
    public function togglePriority($id)
    {
        $userType = session()->get('user_type');
        if ($userType !== 'admin' && $userType !== 'superuser') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $schedule = $this->scheduleModel->find($id);
        if (!$schedule) {
            return $this->response->setJSON(['success' => false, 'message' => 'Schedule not found']);
        }

        $result = $this->scheduleModel->togglePriority($id);
        if ($result) {
            return $this->response->setJSON(['success' => true, 'priority' => (int)!empty($schedule['priority']) ? 0 : 1]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to toggle priority']);
    }
}
