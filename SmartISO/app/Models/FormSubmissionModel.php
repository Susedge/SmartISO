<?php

namespace App\Models;

use CodeIgniter\Model;

class FormSubmissionModel extends Model
{
    protected $table      = 'form_submissions';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    
    protected $allowedFields = [
        'form_id', 'panel_name', 'submitted_by', 'status', 'priority',
        'approver_id', 'approved_at', 'approval_comments',
        'rejected_reason', 'signature_applied', 'service_staff_id',
        'service_staff_signature_date', 'service_notes',
    'requestor_signature_date', 'approver_signature_date', 'completed', 'completion_date',
        // File attachment support
        'reference_file', 'reference_file_original',
        // Optional cancellation timestamp if present
        'cancelled_at'
    ];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
    /**
     * Override insert to create notifications
     */
    public function insert($data = null, bool $returnID = true)
    {
        $result = parent::insert($data, $returnID);
        
        if ($result && $returnID) {
            // Create notification for new submission
            $formModel = new \App\Models\FormModel();
            $form = $formModel->find($data['form_id']);
            
            if ($form) {
                $notificationModel = new \App\Models\NotificationModel();
                $notificationModel->createSubmissionNotification($result, $form['code']);
            }
        }
        
        return $result;
    }

    /**
     * Override update so we can keep related schedule rows in sync when
     * certain submission fields change (service_staff assignment, status changes).
     * This helps ensure edits made from admin/department admin panels are
     * reflected to service staff calendars and prevent stale/duplicate state.
     */
    public function update($id = null, $data = null)
    {
        $result = parent::update($id, $data);

        // If the update succeeded and we received an array of updated data,
        // propagate changes to schedules linked to this submission.
        try {
            if ($result && is_array($data) && !empty($id)) {
                // Normalize id - when called with array of updates, parent::update
                // may accept string/number or array. We expect numeric submission id.
                $submissionId = is_array($id) ? ($id['id'] ?? null) : $id;
                if (is_array($submissionId)) {
                    // Guard - unexpected shape; do not proceed
                    $submissionId = null;
                }
                if (!empty($submissionId)) {
                    $scheduleModelClass = 'App\\Models\\ScheduleModel';
                    if (class_exists($scheduleModelClass)) {
                        $scheduleModel = new \App\Models\ScheduleModel();

                        // If service_staff_id changed, update assigned_staff_id in all schedules
                        if (array_key_exists('service_staff_id', $data) && $data['service_staff_id'] !== null) {
                            $staffId = $data['service_staff_id'];
                            // Update all schedules for this submission to reflect current assignment
                            $schedules = $scheduleModel->where('submission_id', $submissionId)->findAll();
                            foreach ($schedules as $s) {
                                try {
                                    $scheduleModel->update($s['id'], ['assigned_staff_id' => $staffId]);
                                } catch (\Throwable $e) {
                                    log_message('error', 'FormSubmissionModel::update: Failed to sync assigned_staff_id for schedule ' . ($s['id'] ?? 'unknown') . ': ' . $e->getMessage());
                                }
                            }
                            log_message('info', 'FormSubmissionModel::update: Synced assigned_staff_id to schedules for submission ' . $submissionId);
                        }

                        // If status changed to completed, mark related schedules as completed too
                        if (array_key_exists('status', $data) && $data['status'] === 'completed') {
                            $schedules = $scheduleModel->where('submission_id', $submissionId)->findAll();
                            foreach ($schedules as $s) {
                                try {
                                    $scheduleModel->update($s['id'], ['status' => 'completed']);
                                } catch (\Throwable $e) {
                                    log_message('error', 'FormSubmissionModel::update: Failed to set schedule completed for schedule ' . ($s['id'] ?? 'unknown') . ': ' . $e->getMessage());
                                }
                            }
                            log_message('info', 'FormSubmissionModel::update: Marked schedules completed for submission ' . $submissionId);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Non-fatal - log and continue
            log_message('error', 'FormSubmissionModel::update: exception when syncing schedules: ' . $e->getMessage());
        }

        return $result;
    }
    
    /**
     * Get submissions with form details
     */
    public function getSubmissionsWithDetails($userId = null)
    {
        // Get the raw submission data first to make sure we have at least this
        $builder = $this->db->table('form_submissions fs');
        
        // Select all fields from the form_submissions table
        $builder->select('fs.*');
        
        // Try to get form data
        $builder->select('COALESCE(f.code, "Unknown") as form_code, COALESCE(f.description, "Unknown Form") as form_description')
            ->join('forms f', 'f.id = fs.form_id', 'left');
        
        // Try to get user data
        $builder->select('COALESCE(u.full_name, "Unknown User") as submitted_by_name')
            ->join('users u', 'u.id = fs.submitted_by', 'left');
            
        // Get approver details
        $builder->select('approver.full_name as approver_name, approver.signature as approver_signature')
            ->join('users approver', 'approver.id = fs.approver_id', 'left');
        
        // Get schedule details for priority display
        $builder->select('sch.priority_level, sch.eta_days, sch.estimated_date')
            ->join('schedules sch', 'sch.submission_id = fs.id', 'left');
        
        // Filter by user if needed
        if ($userId !== null) {
            $builder->where('fs.submitted_by', $userId);
        }
        
        // Order by creation date
        $builder->orderBy('fs.created_at', 'DESC');
        
        // Get the results
        return $builder->get()->getResultArray();
    }
    
    /**
     * Get submissions pending approval
     */
    public function getPendingApprovals()
    {
        $builder = $this->db->table('form_submissions fs');
        $builder->select('fs.*, f.code as form_code, f.description as form_description, u.full_name as submitted_by_name')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->where('fs.status', 'submitted')
            ->orderBy('fs.created_at', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get submissions pending approval with optional filters
     */
    // NOTE: parameter originally named $officeFilter; repurposed as department filter after org refactor
    public function getPendingApprovalsWithFilters($departmentFilter = null, $priorityFilter = null)
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        $builder = $this->db->table('form_submissions fs');
        $builder->select('fs.*, f.code as form_code, f.description as form_description, 
                          u.full_name as submitted_by_name, d.description as department_name, 
                          o.description as office_name,
                          sch.priority_level, sch.eta_days, sch.estimated_date')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->join('offices o', 'o.id = u.office_id', 'left')
            ->join('schedules sch', 'sch.submission_id = fs.id', 'left')
            ->where('fs.status', 'submitted');
        
        // For approving authority, check if they're assigned as a signatory for the forms
        if ($userType === 'approving_authority') {
            // Join with form_signatories to only show forms this user is assigned to approve
            $builder->join('form_signatories fsig', 'fsig.form_id = f.id', 'inner');
            $builder->where('fsig.user_id', $userId);
        }
        
        // Apply department filter (legacy: previously office filter)
        if (!empty($departmentFilter)) {
            $builder->where('d.description', $departmentFilter);
        }
        
        // Apply priority filter - use priority_level from schedules table
        if (!empty($priorityFilter)) {
            $builder->where('sch.priority_level', $priorityFilter);
        }
        
        $builder->orderBy('fs.created_at', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get submissions pending service
     */
    public function getPendingService()
    {
        $builder = $this->db->table('form_submissions fs');
        $builder->select('fs.*, f.code as form_code, f.description as form_description, 
                          u.full_name as submitted_by_name, approver.full_name as approver_name, d.description as department_name')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('users approver', 'approver.id = fs.approver_id', 'left')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->where('fs.status', 'approved')
            ->where('fs.service_staff_id IS NULL')
            ->orderBy('fs.approved_at', 'ASC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Get submissions pending requestor signature
     */
    public function getPendingRequestorSignature()
    {
        $builder = $this->db->table('form_submissions fs');
        $builder->select('fs.*, f.code as form_code, f.description as form_description')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->where('fs.status', 'approved')
            ->where('fs.service_staff_id IS NOT NULL')
            ->where('fs.service_staff_signature_date IS NOT NULL')
            ->where('fs.requestor_signature_date IS NULL')
            ->orderBy('fs.service_staff_signature_date', 'ASC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Get completed submissions
     */
    public function getCompletedSubmissions()
    {
        $builder = $this->db->table('form_submissions fs');
        $builder->select('fs.*, f.code as form_code, f.description as form_description')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->where('fs.completed', 1)
            ->orderBy('fs.completion_date', 'DESC');
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * Approve a submission
     */
    public function approveSubmission($submissionId, $approverId, $comments = '')
    {
        $result = $this->update($submissionId, [
            'status' => 'approved',
            'approver_id' => $approverId,
            'approved_at' => date('Y-m-d H:i:s'),
            // persist explicit approver signature timestamp for downstream templating
            'approver_signature_date' => date('Y-m-d H:i:s'),
            'approval_comments' => $comments,
            'signature_applied' => 1
        ]);
        
        if ($result) {
            // Create notification for requestor
            $submission = $this->find($submissionId);
            $notificationModel = new \App\Models\NotificationModel();
            $notificationModel->createApprovalNotification($submissionId, $submission['submitted_by'], true);

            // Ensure a schedule exists for newly approved submissions.
            // We intentionally create a schedule even when assigned_staff_id
            // is NULL so that approved submissions are visible on calendars
            // and downstream UI flows. This mirrors the behavior of
            // Forms::submitApproval which creates schedules on approval.
            try {
                $this->createScheduleOnApproval($submissionId);
            } catch (\Throwable $e) {
                // Non-fatal - log for diagnostics
                log_message('error', '[approveSubmission] Failed to auto-create schedule for submission ' . $submissionId . ': ' . $e->getMessage());
            }
        }
        
        return $result;
    }

    /**
     * Create a schedule row when a submission is approved.
     * - If a schedule already exists for this submission, this method will update the
     *   existing row with approved date and assigned_staff_id when possible.
     * - If no schedule exists, it will insert a pending schedule using approval date
     *   and reasonable defaults to make the item visible on calendars immediately.
     */
    public function createScheduleOnApproval(int $submissionId)
    {
        $submission = $this->find($submissionId);
        if (empty($submission)) {
            throw new \InvalidArgumentException('Submission not found: ' . $submissionId);
        }

        // Only proceed when ScheduleModel is available
        if (!class_exists('App\\Models\\ScheduleModel')) {
            throw new \RuntimeException('ScheduleModel not found');
        }

        $scheduleModel = new \App\Models\ScheduleModel();

        // If schedule exists, update assigned_staff and scheduled_date to approval date
        $existing = $scheduleModel->where('submission_id', $submissionId)->first();

        // Use today's date as approval/scheduled date
        $approvalDate = date('Y-m-d');

        // Map submission priority to schedule priority_level
        $priorityMapping = [
            'low' => 'low',
            'normal' => 'medium',
            'medium' => 'medium',
            'high' => 'high',
            'urgent' => 'high',
            'critical' => 'high'
        ];
        $submissionPriority = $submission['priority'] ?? 'low';
        $schedulePriority = $priorityMapping[$submissionPriority] ?? 'low';

        // Compute ETA for mapping (low => 7 calendar days, medium => 5 business days, high => 3 business days)
        $etaDays = null; $estimatedDate = null;
        if ($schedulePriority === 'low') {
            $etaDays = 7;
            $estimatedDate = date('Y-m-d', strtotime($approvalDate . ' +7 days'));
        } elseif ($schedulePriority === 'medium') {
            try {
                $schCtrl = new \App\Controllers\Schedule();
                $estimatedDate = $schCtrl->addBusinessDays($approvalDate, 5);
            } catch (\Throwable $e) {
                $estimatedDate = date('Y-m-d', strtotime($approvalDate . ' +5 days'));
            }
            $etaDays = 5;
        } else {
            try {
                $schCtrl = new \App\Controllers\Schedule();
                $estimatedDate = $schCtrl->addBusinessDays($approvalDate, 3);
            } catch (\Throwable $e) {
                $estimatedDate = date('Y-m-d', strtotime($approvalDate . ' +3 days'));
            }
            $etaDays = 3;
        }

        if ($existing) {
            // Update schedule with approval date and assigned_staff_id (if present on submission)
            $update = [
                'scheduled_date' => $approvalDate,
                'priority_level' => $schedulePriority,
                'eta_days' => $etaDays,
                'estimated_date' => $estimatedDate
            ];
            // Preserve assigned staff if submission has one
            if (!empty($submission['service_staff_id'])) {
                $update['assigned_staff_id'] = $submission['service_staff_id'];
            }
            $scheduleModel->update($existing['id'], $update);
            return true;
        }

        // Create new schedule
        $schedData = [
            'submission_id' => $submissionId,
            'scheduled_date' => $approvalDate,
            'scheduled_time' => '09:00:00',
            'duration_minutes' => 60,
            'assigned_staff_id' => $submission['service_staff_id'] ?? null,
            'priority_level' => $schedulePriority,
            'location' => '',
            'notes' => 'Auto-created schedule on approval',
            'status' => 'pending',
            'eta_days' => $etaDays,
            'estimated_date' => $estimatedDate
        ];

        return (bool)$scheduleModel->insert($schedData);
    }
    
    /**
     * Reject a submission
     */
    public function rejectSubmission($submissionId, $approverId, $reason = '')
    {
        $result = $this->update($submissionId, [
            'status' => 'rejected',
            'approver_id' => $approverId,
            'approved_at' => date('Y-m-d H:i:s'),
            'rejected_reason' => $reason
        ]);
        
        if ($result) {
            // Create notification for requestor
            $submission = $this->find($submissionId);
            $notificationModel = new \App\Models\NotificationModel();
            $notificationModel->createApprovalNotification($submissionId, $submission['submitted_by'], false);
        }
        
        return $result;
    }
    
    /**
     * Mark a submission as serviced
     */
    public function markAsServiced($submissionId, $serviceStaffId, $notes = '')
    {
        $result = $this->update($submissionId, [
            'service_staff_id' => $serviceStaffId,
            'service_staff_signature_date' => date('Y-m-d H:i:s'),
            'service_notes' => $notes,
            'status' => 'completed',
            'completed' => 1,
            'completion_date' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            // Create notification for requestor
            $submission = $this->find($submissionId);
            $notificationModel = new \App\Models\NotificationModel();
            try {
                $notified = $notificationModel->createServiceCompletionNotification($submissionId, $submission['submitted_by']);
                if ($notified) {
                    log_message('info', "markAsServiced: Service completion notification created for submission {$submissionId}, user {$submission['submitted_by']}");
                } else {
                    log_message('warning', "markAsServiced: Service completion notification FAILED for submission {$submissionId}, user {$submission['submitted_by']}");
                }
            } catch (\Throwable $e) {
                log_message('error', 'markAsServiced: Exception creating service completion notification: ' . $e->getMessage());
            }

            // Diagnostic: dump notification rows for this submission to logs to help debugging
            try {
                $db = \Config\Database::connect();
                $rows = $db->table('notifications')->where('submission_id', $submissionId)->orderBy('created_at', 'ASC')->get()->getResultArray();
                log_message('info', 'markAsServiced: notification rows for submission ' . $submissionId . ': ' . json_encode($rows));
            } catch (\Throwable $e) {
                log_message('error', 'markAsServiced: failed to fetch notification rows for submission ' . $submissionId . ': ' . $e->getMessage());
            }
        }
        
        return $result;
    }

    /**
     * Assign service staff to a submission
     */
    public function assignServiceStaff($submissionId, $serviceStaffId)
    {
        $result = $this->update($submissionId, [
            'service_staff_id' => $serviceStaffId
        ]);
        
        if ($result) {
            // Get form code for notification
            $submission = $this->getSubmissionsWithDetails();
            $currentSubmission = array_filter($submission, function($item) use ($submissionId) {
                return $item['id'] == $submissionId;
            });
            
            if (!empty($currentSubmission)) {
                $currentSubmission = reset($currentSubmission);
                $notificationModel = new \App\Models\NotificationModel();
                $notificationModel->createServiceStaffAssignmentNotification(
                    $submissionId, 
                    $serviceStaffId, 
                    $currentSubmission['form_code']
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Mark a submission as completed
     */
    public function markAsCompleted($submissionId)
    {
        $result = $this->update($submissionId, [
            'requestor_signature_date' => date('Y-m-d H:i:s'),
            'completed' => 1,
            'completion_date' => date('Y-m-d H:i:s'),
            'status' => 'completed'
        ]);

        if ($result) {
            // Create notification for requestor (they completed the form) to keep behavior consistent
            // with service staff completions. This ensures requestor receives the same "Service Completed"
            // notification and associated audit notifications are created.
            try {
                $submission = $this->find($submissionId);
                $notificationModel = new \App\Models\NotificationModel();
                $notified = $notificationModel->createServiceCompletionNotification($submissionId, $submission['submitted_by']);
                if ($notified) {
                    log_message('info', "markAsCompleted: Service completion notification created for submission {$submissionId}, user {$submission['submitted_by']}");
                } else {
                    log_message('warning', "markAsCompleted: Service completion notification FAILED for submission {$submissionId}, user {$submission['submitted_by']}");
                }
            } catch (\Throwable $e) {
                log_message('error', 'markAsCompleted: Exception creating service completion notification: ' . $e->getMessage());
            }

            // Diagnostic: dump notifications for this submission to logs for easy debugging
            try {
                $db = \Config\Database::connect();
                $rows = $db->table('notifications')->where('submission_id', $submissionId)->orderBy('created_at', 'ASC')->get()->getResultArray();
                log_message('info', 'markAsCompleted: notification rows for submission ' . $submissionId . ': ' . json_encode($rows));
            } catch (\Throwable $e) {
                log_message('error', 'markAsCompleted: failed to fetch notification rows for submission ' . $submissionId . ': ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Cancel a submission by the requestor.
     * Ensures submission belongs to user and is not already completed or serviced.
     */
    public function cancelSubmission($submissionId, $userId)
    {
        $submission = $this->find($submissionId);
        if (empty($submission)) {
            return false;
        }

        // Only requestor can cancel their submission
        if ($submission['submitted_by'] != $userId) {
            return false;
        }

        // Do not allow cancellation if already completed or serviced
        if (!empty($submission['completed']) && $submission['completed'] == 1) {
            return false;
        }

        if (!empty($submission['service_staff_signature_date']) || !empty($submission['requestor_signature_date'])) {
            return false;
        }

        $update = ['status' => 'cancelled'];

        if ($this->db->fieldExists('cancelled_at', 'form_submissions')) {
            $update['cancelled_at'] = date('Y-m-d H:i:s');
        }

        $result = $this->update($submissionId, $update);

        if ($result) {
            // Notify approver if present that the request was cancelled
            try {
                $notificationModel = new \App\Models\NotificationModel();
                if (!empty($submission['approver_id'])) {
                    // Try to get form code for context
                    $formModel = new \App\Models\FormModel();
                    $form = $formModel->find($submission['form_id']);
                    $formCode = $form['code'] ?? '';
                    $notificationModel->createCancellationNotification($submissionId, $submission['approver_id'], $formCode);
                }
            } catch (\Exception $e) {
                // Swallow notification errors but keep cancellation success
                log_message('error', 'Failed to create cancellation notification: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Check if a submission is completed.
     * Accepts either a submission array or an ID.
     * Returns boolean.
     */
    public function isCompleted($submissionOrId): bool
    {
        if (is_array($submissionOrId)) {
            $submission = $submissionOrId;
        } else {
            $submission = $this->find($submissionOrId);
            if (empty($submission)) {
                return false;
            }
        }

        // Consider completed if completed flag is set or status is 'completed'
        if (!empty($submission['completed']) && $submission['completed'] == 1) {
            return true;
        }

        if (!empty($submission['status']) && $submission['status'] === 'completed') {
            return true;
        }

        return false;
    }
}
