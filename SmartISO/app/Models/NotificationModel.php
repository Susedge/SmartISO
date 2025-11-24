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
     * Helper: insert notification row and send email with logging
     */
    protected function notifyUser(int $userId, string $title, string $message, ?int $submissionId = null): bool
    {
        try {
            $insertData = [
                'user_id'    => $userId,
                'title'      => $title,
                'message'    => $message,
                'read'       => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            if ($submissionId !== null) {
                $insertData['submission_id'] = $submissionId;
            }

            $res = $this->insert($insertData);
            if ($res === false) {
                log_message('error', "Notification insert failed for user {$userId} (submission: " . ($submissionId ?? 'null') . ")");
            }

            $sent = $this->sendEmailNotification($userId, $title, $message);
            if ($sent) {
                log_message('info', "Notification email sent to user {$userId} (submission: " . ($submissionId ?? 'null') . ")");
            } else {
                log_message('warning', "Notification email NOT sent to user {$userId} (submission: " . ($submissionId ?? 'null') . ")");
            }

            return ($res !== false);
        } catch (\Throwable $e) {
            log_message('error', 'notifyUser exception: ' . $e->getMessage());
            return false;
        }
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
     * For department admins: only show notifications from their own department
     */
    public function getUserNotifications($userId, $limit = 20, $unreadOnly = false)
    {
        // Get user type and department for filtering
        $userModel = new UserModel();
        $user = $userModel->find($userId);
        $userType = $user['user_type'] ?? null;
        $userDepartment = $user['department_id'] ?? null;
        
        // Build base query
        $builder = $this->db->table($this->table . ' n');
        $builder->select('n.*');
        $builder->where('n.user_id', $userId);
        
        // For department admins: filter by submission's FORM department (forms belong to departments)
        if ($userType === 'department_admin' && $userDepartment) {
            $builder->join('form_submissions fs', 'fs.id = n.submission_id', 'left');
            $builder->join('forms f', 'f.id = fs.form_id', 'left');
            $builder->groupStart()
                    ->where('n.submission_id IS NULL', null, false) // Include non-submission notifications
                    ->orWhere('f.department_id', $userDepartment) // Or submissions for forms from same department
                    ->groupEnd();
            
            log_message('info', "Filtering notifications for department_admin (User ID: {$userId}, Dept: {$userDepartment}) by FORM department");
        }
        
        if ($unreadOnly) {
            $builder->where('n.read', 0);
        }
        
        $builder->orderBy('n.created_at', 'DESC')
                ->limit($limit);
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get unread count for a user
     * For department admins: only count notifications from their own department
     */
    public function getUnreadCount($userId)
    {
        // Get user type and department for filtering
        $userModel = new UserModel();
        $user = $userModel->find($userId);
        $userType = $user['user_type'] ?? null;
        $userDepartment = $user['department_id'] ?? null;
        
        // Build query
        $builder = $this->db->table($this->table . ' n');
        $builder->where('n.user_id', $userId)
                ->where('n.read', 0);
        
        // For department admins: filter by submission's FORM department (forms belong to departments)
        if ($userType === 'department_admin' && $userDepartment) {
            $builder->join('form_submissions fs', 'fs.id = n.submission_id', 'left');
            $builder->join('forms f', 'f.id = fs.form_id', 'left');
            $builder->groupStart()
                    ->where('n.submission_id IS NULL', null, false) // Include non-submission notifications
                    ->orWhere('f.department_id', $userDepartment) // Or submissions for forms from same department
                    ->groupEnd();
        }
        
        return $builder->countAllResults();
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
     * Create notification for new submission - notify assigned approvers AND admins
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
        
        log_message('info', "Submission Notification - Submission ID: {$submissionId} | Form: {$formCode} | Submitter Dept: {$submitterDepartment} | Form Signatories: " . count($assignedApprovers));
        
        // If no specific approvers assigned, fall back to approving authorities FROM THE SAME DEPARTMENT
        if (empty($assignedApprovers)) {
            if ($submitterDepartment) {
                // Include both approving authorities and department admins from the same department
                $assignedApprovers = $userModel->whereIn('user_type', ['approving_authority', 'department_admin'])
                                               ->where('department_id', $submitterDepartment)
                                               ->where('active', 1)
                                               ->findAll();
                log_message('info', "Submission Notification - No form signatories, using department-based routing | Department: {$submitterDepartment} | Found " . count($assignedApprovers) . " approvers/dept admins");
            } else {
                // No department - notify all approvers (legacy support for data without departments)
                $assignedApprovers = $userModel->whereIn('user_type', ['approving_authority', 'department_admin'])
                                               ->where('active', 1)
                                               ->findAll();
                log_message('warning', "Submission Notification - No department assigned, notifying ALL approvers (legacy mode)");
            }
        }
        
        // ALWAYS notify global admins regardless of department
        $globalAdmins = $userModel->whereIn('user_type', ['admin', 'superuser'])
                                  ->where('active', 1)
                                  ->findAll();

        // Also ALWAYS include department admins for submitter's department (explicit requirement)
        $deptAdmins = [];
        if ($submitterDepartment) {
            $deptAdmins = $userModel->where('user_type', 'department_admin')
                                   ->where('department_id', $submitterDepartment)
                                   ->where('active', 1)
                                   ->findAll();
        }

        // Merge approvers, dept admins and global admins, removing duplicates
        $allNotifyUsers = array_merge($assignedApprovers, $deptAdmins, $globalAdmins);
        $notifiedUserIds = [];

        $title = 'New Service Request Requires Approval';
        $message = "A new {$formCode} request has been submitted by " . ($submitter['full_name'] ?? 'a user') . " and requires approval.";

        foreach ($allNotifyUsers as $user) {
            $userId = isset($user['user_id']) ? $user['user_id'] : ($user['id'] ?? null);
            if (empty($userId)) continue;

            // Skip duplicates
            if (in_array($userId, $notifiedUserIds)) {
                continue;
            }
            $notifiedUserIds[] = $userId;

            $userName = $user['full_name'] ?? $user['username'] ?? "User {$userId}";
            $userType = $user['user_type'] ?? 'unknown';
            $userDept = $user['department_id'] ?? 'N/A';
            log_message('info', "Submission Notification - Notifying: {$userName} (ID: {$userId}, Type: {$userType}, Dept: {$userDept})");

            // Insert and send email via helper
            $this->notifyUser((int)$userId, $title, $message, (int)$submissionId);
        }

        log_message('info', "Submission Notification - Total users notified: " . count($notifiedUserIds));
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
        // Notify the requestor using helper
        $this->notifyUser((int)$userId, $title, $message, (int)$submissionId);

        // Also notify department admins and global admins for visibility (audit)
        try {
            $userModel = new UserModel();
            $submissionModel = new \App\Models\FormSubmissionModel();
            $submission = $submissionModel->find($submissionId);
            $submitterDept = $submission['department_id'] ?? null;

            $admins = $userModel->whereIn('user_type', ['admin', 'superuser'])->where('active', 1)->findAll();
            foreach ($admins as $adm) {
                $this->notifyUser((int)($adm['id'] ?? $adm['user_id']), $title, "Submission #{$submissionId} has been {$status}.", (int)$submissionId);
            }

            if ($submitterDept) {
                $deptAdmins = $userModel->where('user_type', 'department_admin')->where('department_id', $submitterDept)->where('active', 1)->findAll();
                foreach ($deptAdmins as $d) {
                    $this->notifyUser((int)($d['id'] ?? $d['user_id']), $title, "Submission #{$submissionId} has been {$status}.", (int)$submissionId);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'createApprovalNotification extra-notify error: ' . $e->getMessage());
        }
    }

    /**
     * Create notification for schedule
     */
    public function createScheduleNotification($scheduleId, $userId, $scheduledDate, $scheduledTime)
    {
        $title = 'Service Scheduled';
        $message = "Your service has been scheduled for {$scheduledDate} at {$scheduledTime}.";
        $this->notifyUser((int)$userId, $title, $message, (int)$scheduleId);
    }

    /**
     * Create notification for service staff assignment
     */
    public function createServiceStaffAssignmentNotification($submissionId, $serviceStaffId, $formCode)
    {
        $title = 'New Service Assignment';
        $message = "You have been assigned to process a {$formCode} service request. Please review and complete the service.";
        $this->notifyUser((int)$serviceStaffId, $title, $message, (int)$submissionId);
    }

    /**
     * Create notification for service completion
     */
    public function createServiceCompletionNotification($submissionId, $userId)
    {
        $title = 'Service Completed';
        $message = 'Your service request has been completed successfully. You can now provide feedback about your experience.';
        log_message('info', "Service Completion Notification - Submission ID: {$submissionId} | Requestor User ID: {$userId}");

        // Notify requestor
        $requestorNotified = $this->notifyUser((int)$userId, $title, $message, (int)$submissionId);

        // Also notify approver, department admins, and service staff for audit and visibility
        try {
            $submission = (new \App\Models\FormSubmissionModel())->find($submissionId);
            $formId = $submission['form_id'] ?? null;

            // Notify approver if exists
            if (!empty($submission['approver_id'])) {
                $this->notifyUser((int)$submission['approver_id'], 'Request Completed', "Submission #{$submissionId} has been completed by service staff.", (int)$submissionId);
            }

            // Notify department admins by form's department
            if ($formId) {
                $form = (new \App\Models\FormModel())->find($formId);
                $formDept = $form['department_id'] ?? null;
                if ($formDept) {
                    $userModel = new UserModel();
                    $deptAdmins = $userModel->where('user_type', 'department_admin')->where('department_id', $formDept)->where('active', 1)->findAll();
                    foreach ($deptAdmins as $d) {
                        $this->notifyUser((int)($d['id'] ?? $d['user_id']), 'Request Completed', "Submission #{$submissionId} has been completed.", (int)$submissionId);
                    }
                }
            }

            // Optionally notify assigned service staff for record
            if (!empty($submission['service_staff_id'])) {
                $this->notifyUser((int)$submission['service_staff_id'], 'Request Completed', "You marked Submission #{$submissionId} as completed.", (int)$submissionId);
            }
        } catch (\Throwable $e) {
            log_message('error', 'createServiceCompletionNotification extra notifications failed: ' . $e->getMessage());
        }

        return (bool)$requestorNotified;
    }

    /**
     * Create notification for cancellation initiated by requestor
     */
    public function createCancellationNotification($submissionId, $userId, $formCode = '')
    {
        $title = 'Request Cancelled';
        $message = 'A service request' . (!empty($formCode) ? " ({$formCode})" : '') . ' has been cancelled by the requestor.';
        $this->notifyUser((int)$userId, $title, $message, (int)$submissionId);
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
