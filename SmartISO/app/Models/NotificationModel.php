<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\EmailService;

class NotificationModel extends Model
{
    protected $table      = 'notifications';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;
    
    protected $returnType     = 'array';
    
    protected $allowedFields = [
        // Columns present in the current notifications table migration
        'user_id', 'submission_id', 'title', 'message', 'read', 'created_at'
    ];
    
    // Dates
    // The notifications table in migrations only defines a created_at column
    // and does not include updated_at or other newer notification columns.
    // Disable automatic timestamps to avoid inserting an 'updated_at' column
    // that doesn't exist in the DB schema.
    protected $useTimestamps = false;
    
    // Validation
    // Keep minimal validation for the fields we will actually insert.
    protected $validationRules = [
        'user_id' => 'required|integer',
        'title'   => 'required|max_length[255]',
        'message' => 'required'
    ];

    protected $emailService;

    public function __construct()
    {
        parent::__construct();
        $this->emailService = new EmailService();
    }

    /**
     * Send email notification to user
     * 
     * @param int $userId User ID to send email to
     * @param string $title Email subject
     * @param string $message Email message
     * @return bool Success status
     */
    protected function sendEmailNotification($userId, $title, $message)
    {
        try {
            // Get user email
            $userModel = new UserModel();
            $user = $userModel->find($userId);
            
            if (!$user || empty($user['email'])) {
                log_message('warning', "Cannot send email to user {$userId}: No email address found");
                return false;
            }

            $userName = $user['full_name'] ?? $user['username'] ?? '';
            $userEmail = $user['email'];

            // Send email
            return $this->emailService->sendNotificationEmail($userEmail, $userName, $title, $message);
            
        } catch (\Exception $e) {
            log_message('error', "Failed to send email notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notifications for a user
     */
    public function getUserNotifications($userId, $limit = 20, $unreadOnly = false)
    {
        $builder = $this->where('user_id', $userId);
        
        if ($unreadOnly) {
            // notifications table uses `read` column per migration
            $builder->where('read', 0);
        }
        
        return $builder->orderBy('created_at', 'DESC')
                      ->limit($limit)
                      ->findAll();
    }

    /**
     * Get unread count for a user
     */
    public function getUnreadCount($userId)
    {
    return $this->where('user_id', $userId)
           ->where('read', 0)
           ->countAllResults();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId = null)
    {
        // Validate input
        if (empty($notificationId)) {
            return false;
        }
        // Use the DB table builder directly to avoid Model::update signature ambiguity
    $db = \Config\Database::connect();
        $builder = $db->table($this->table)->where('id', $notificationId);
        if ($userId) {
            $builder->where('user_id', $userId);
        }

        try {
            $res = $builder->update(['read' => 1]);
            // Normalize: only boolean false indicates a DB failure; 0 affected rows means already-read (idempotent)
            if ($res === false) {
                // Capture DB driver error and last query for diagnosis
                try {
                    $db = \Config\Database::connect();
                    $dberr = method_exists($db, 'error') ? $db->error() : null;
                    $lastQuery = method_exists($db, 'getLastQuery') ? $db->getLastQuery() : null;
                    $log = [
                        'time' => date('c'),
                        'notificationId' => $notificationId,
                        'userId' => $userId,
                        'dbError' => $dberr,
                        'lastQuery' => $lastQuery
                    ];
                    @file_put_contents(WRITEPATH . 'logs' . DIRECTORY_SEPARATOR . 'notifications_db_errors.log', json_encode($log) . PHP_EOL, FILE_APPEND | LOCK_EX);
                } catch (\Exception $e) {
                    log_message('error', 'Failed to write notifications DB debug log: ' . $e->getMessage());
                }
            }
            return ($res === false) ? false : true;
        } catch (\CodeIgniter\Database\Exceptions\DataException $e) {
            // Defensive: log and return false rather than throw up to the request
            log_message('error', 'Failed to mark notification as read: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId)
    {
        if (empty($userId)) {
            return false;
        }
        try {
            // Use DB table builder for bulk update
            $db = \Config\Database::connect();
            $res = $db->table($this->table)
                ->where('user_id', $userId)
                ->where('read', 0)
                ->update(['read' => 1]);
         // Normalize: treat only boolean false as failure
         if ($res === false) {
                try {
                    $db = \Config\Database::connect();
                    $dberr = method_exists($db, 'error') ? $db->error() : null;
                    $lastQuery = method_exists($db, 'getLastQuery') ? $db->getLastQuery() : null;
                    $log = [
                        'time' => date('c'),
                        'userId' => $userId,
                        'dbError' => $dberr,
                        'lastQuery' => $lastQuery
                    ];
                    @file_put_contents(WRITEPATH . 'logs' . DIRECTORY_SEPARATOR . 'notifications_db_errors.log', json_encode($log) . PHP_EOL, FILE_APPEND | LOCK_EX);
                } catch (\Exception $e) {
                    log_message('error', 'Failed to write notifications DB debug log: ' . $e->getMessage());
                }
            }
            return ($res === false) ? false : true;
        } catch (\CodeIgniter\Database\Exceptions\DataException $e) {
            log_message('error', 'Failed to mark all notifications as read: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create notification for new submission - notify only assigned approvers
     */
    public function createSubmissionNotification($submissionId, $formCode)
    {
        // Get submission details
        $submissionModel = new \App\Models\FormSubmissionModel();
        $submission = $submissionModel->find($submissionId);
        
        if (!$submission) return;
        
        // Get submitter's department
        $userModel = new UserModel();
        $submitter = $userModel->find($submission['submitted_by']);
        $submitterDepartment = $submitter['department_id'] ?? null;
        
        // Get form-specific assigned approvers
        $formSignatoryModel = new \App\Models\FormSignatoryModel();
        $assignedApprovers = $formSignatoryModel->getFormSignatories($submission['form_id']);
        
        // If no specific approvers assigned, fall back to approving authorities FROM THE SAME DEPARTMENT
        if (empty($assignedApprovers)) {
            if ($submitterDepartment) {
                // Only notify approvers from the same department
                $assignedApprovers = $userModel->where('user_type', 'approving_authority')
                                               ->where('department_id', $submitterDepartment)
                                               ->where('active', 1)
                                               ->findAll();
            } else {
                // No department - notify all (legacy support for data without departments)
                $assignedApprovers = $userModel->getUsersByType('approving_authority');
            }
        }
        
        $title = 'New Service Request Requires Approval';
        $message = "A new {$formCode} request has been submitted by " . ($submission['submitted_by_name'] ?? 'a user') . " and requires your approval.";
        
        foreach ($assignedApprovers as $approver) {
            $userId = isset($approver['user_id']) ? $approver['user_id'] : $approver['id'];

            // Insert only columns that exist in the current notifications table.
            $this->insert([
                'user_id'       => $userId,
                'submission_id' => $submissionId,
                'title'         => $title,
                'message'       => $message,
                'read'          => 0,
                'created_at'    => date('Y-m-d H:i:s')
            ]);

            // Send email notification
            $this->sendEmailNotification($userId, $title, $message);
        }
    }

    /**
     * Create notification for approval
     */
    public function createApprovalNotification($submissionId, $userId, $approved = true)
    {
        $status = $approved ? 'approved' : 'rejected';
        $title = 'Request ' . ucfirst($status);
        $message = $approved ? 
            'Your service request has been approved and will be scheduled.' : 
            'Your service request has been rejected. Please check the comments for details.';
        
        $this->insert([
            'user_id'       => $userId,
            'submission_id' => $submissionId,
            'title'         => $title,
            'message'       => $message,
            'read'          => 0,
            'created_at'    => date('Y-m-d H:i:s')
        ]);

        // Send email notification
        $this->sendEmailNotification($userId, $title, $message);
    }

    /**
     * Create notification for schedule
     */
    public function createScheduleNotification($scheduleId, $userId, $scheduledDate, $scheduledTime)
    {
        $title = 'Service Scheduled';
        $message = "Your service has been scheduled for {$scheduledDate} at {$scheduledTime}.";
        
        $this->insert([
            'user_id'    => $userId,
            'title'      => $title,
            'message'    => $message,
            'read'       => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Send email notification
        $this->sendEmailNotification($userId, $title, $message);
    }

    /**
     * Create notification for service staff assignment
     */
    public function createServiceStaffAssignmentNotification($submissionId, $serviceStaffId, $formCode)
    {
        $title = 'New Service Assignment';
        $message = "You have been assigned to process a {$formCode} service request. Please review and complete the service.";
        
        $this->insert([
            'user_id'       => $serviceStaffId,
            'submission_id' => $submissionId,
            'title'         => $title,
            'message'       => $message,
            'read'          => 0,
            'created_at'    => date('Y-m-d H:i:s')
        ]);

        // Send email notification
        $this->sendEmailNotification($serviceStaffId, $title, $message);
    }

    /**
     * Create notification for service completion
     */
    public function createServiceCompletionNotification($submissionId, $userId)
    {
        $title = 'Service Completed';
        $message = 'Your service request has been completed successfully. You can now provide feedback about your experience.';
        
        $this->insert([
            'user_id'       => $userId,
            'submission_id' => $submissionId,
            'title'         => $title,
            'message'       => $message,
            'read'          => 0,
            'created_at'    => date('Y-m-d H:i:s')
        ]);

        // Send email notification
        $this->sendEmailNotification($userId, $title, $message);
    }

    /**
     * Create notification for cancellation initiated by requestor
     */
    public function createCancellationNotification($submissionId, $userId, $formCode = '')
    {
        $title = 'Request Cancelled';
        $message = 'A service request' . (!empty($formCode) ? " ({$formCode})" : '') . ' has been cancelled by the requestor.';
        
        $this->insert([
            'user_id'       => $userId,
            'submission_id' => $submissionId,
            'title'         => $title,
            'message'       => $message,
            'read'          => 0,
            'created_at'    => date('Y-m-d H:i:s')
        ]);

        // Send email notification
        $this->sendEmailNotification($userId, $title, $message);
    }

    /**
     * Clean up old notifications (older than 30 days)
     */
    public function cleanupOldNotifications()
    {
        $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
        return $this->where('created_at <', $thirtyDaysAgo)->delete();
    }
}
