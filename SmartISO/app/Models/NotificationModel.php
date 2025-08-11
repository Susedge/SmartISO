<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table      = 'notifications';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;
    
    protected $returnType     = 'array';
    
    protected $allowedFields = [
        'user_id', 'title', 'message', 'type', 'related_id', 'related_type',
        'is_read', 'action_url', 'priority'
    ];
    
    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
    // Validation
    protected $validationRules = [
        'user_id'      => 'required|integer',
        'title'        => 'required|max_length[255]',
        'message'      => 'required',
        'type'         => 'required|in_list[info,success,warning,error,submission,approval,schedule,service]',
        'priority'     => 'permit_empty|in_list[low,normal,high,urgent]'
    ];

    /**
     * Get notifications for a user
     */
    public function getUserNotifications($userId, $limit = 20, $unreadOnly = false)
    {
        $builder = $this->where('user_id', $userId);
        
        if ($unreadOnly) {
            $builder->where('is_read', 0);
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
                   ->where('is_read', 0)
                   ->countAllResults();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId = null)
    {
        $builder = $this->where('id', $notificationId);
        
        if ($userId) {
            $builder->where('user_id', $userId);
        }
        
        return $builder->update(['is_read' => 1]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId)
    {
        return $this->where('user_id', $userId)
                   ->where('is_read', 0)
                   ->update(['is_read' => 1]);
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
        
        // Get form-specific assigned approvers
        $formSignatoryModel = new \App\Models\FormSignatoryModel();
        $assignedApprovers = $formSignatoryModel->getFormSignatories($submission['form_id']);
        
        // If no specific approvers assigned, fall back to all approving authorities
        if (empty($assignedApprovers)) {
            $userModel = new UserModel();
            $assignedApprovers = $userModel->getUsersByType('approving_authority');
        }
        
        foreach ($assignedApprovers as $approver) {
            $userId = isset($approver['user_id']) ? $approver['user_id'] : $approver['id'];
            
            $this->insert([
                'user_id'      => $userId,
                'title'        => 'New Service Request Requires Approval',
                'message'      => "A new {$formCode} request has been submitted by " . ($submission['submitted_by_name'] ?? 'a user') . " and requires your approval.",
                'type'         => 'submission',
                'related_id'   => $submissionId,
                'related_type' => 'form_submission',
                'action_url'   => '/forms/pending-approval',
                'priority'     => 'normal'
            ]);
        }
    }

    /**
     * Create notification for approval
     */
    public function createApprovalNotification($submissionId, $userId, $approved = true)
    {
        $status = $approved ? 'approved' : 'rejected';
        $message = $approved ? 
            'Your service request has been approved and will be scheduled.' : 
            'Your service request has been rejected. Please check the comments for details.';
        
        $this->insert([
            'user_id'      => $userId,
            'title'        => 'Request ' . ucfirst($status),
            'message'      => $message,
            'type'         => 'approval',
            'related_id'   => $submissionId,
            'related_type' => 'form_submission',
            'action_url'   => '/dashboard/my-requests',
            'priority'     => $approved ? 'normal' : 'high'
        ]);
    }

    /**
     * Create notification for schedule
     */
    public function createScheduleNotification($scheduleId, $userId, $scheduledDate, $scheduledTime)
    {
        $this->insert([
            'user_id'      => $userId,
            'title'        => 'Service Scheduled',
            'message'      => "Your service has been scheduled for {$scheduledDate} at {$scheduledTime}.",
            'type'         => 'schedule',
            'related_id'   => $scheduleId,
            'related_type' => 'schedule',
            'action_url'   => '/dashboard/schedules',
            'priority'     => 'normal'
        ]);
    }

    /**
     * Create notification for service staff assignment
     */
    public function createServiceStaffAssignmentNotification($submissionId, $serviceStaffId, $formCode)
    {
        $this->insert([
            'user_id'      => $serviceStaffId,
            'title'        => 'New Service Assignment',
            'message'      => "You have been assigned to process a {$formCode} service request. Please review and complete the service.",
            'type'         => 'service',
            'related_id'   => $submissionId,
            'related_type' => 'form_submission',
            'action_url'   => '/forms/pending-service',
            'priority'     => 'normal'
        ]);
    }

    /**
     * Create notification for service completion
     */
    public function createServiceCompletionNotification($submissionId, $userId)
    {
        $this->insert([
            'user_id'      => $userId,
            'title'        => 'Service Completed',
            'message'      => 'Your service request has been completed successfully. You can now provide feedback about your experience.',
            'type'         => 'service',
            'related_id'   => $submissionId,
            'related_type' => 'form_submission',
            'action_url'   => '/feedback/create/' . $submissionId,
            'priority'     => 'normal'
        ]);
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
