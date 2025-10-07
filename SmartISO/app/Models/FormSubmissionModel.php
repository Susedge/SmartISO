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
                          o.description as office_name')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->join('offices o', 'o.id = u.office_id', 'left')
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
        
        // Apply priority filter
        if (!empty($priorityFilter)) {
            $builder->where('fs.priority', $priorityFilter);
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
        }
        
        return $result;
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
            'status' => 'completed'
        ]);
        
        if ($result) {
            // Create notification for requestor
            $submission = $this->find($submissionId);
            $notificationModel = new \App\Models\NotificationModel();
            $notificationModel->createServiceCompletionNotification($submissionId, $submission['submitted_by']);
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
        return $this->update($submissionId, [
            'requestor_signature_date' => date('Y-m-d H:i:s'),
            'completed' => 1,
            'completion_date' => date('Y-m-d H:i:s'),
            'status' => 'completed'
        ]);
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
