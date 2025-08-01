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
        'form_id', 'panel_name', 'submitted_by', 'status', 
        'approver_id', 'approved_at', 'approval_comments',
        'rejected_reason', 'signature_applied', 'service_staff_id',
        'service_staff_signature_date', 'service_notes',
        'requestor_signature_date', 'completed', 'completion_date'
    ];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
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
    public function getPendingApprovalsWithFilters($departmentFilter = null, $priorityFilter = null)
    {
        $builder = $this->db->table('form_submissions fs');
        $builder->select('fs.*, f.code as form_code, f.description as form_description, 
                          u.full_name as submitted_by_name, d.description as department_name')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->where('fs.status', 'submitted');
        
        // Apply department filter
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
                          u.full_name as submitted_by_name, approver.full_name as approver_name')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('users approver', 'approver.id = fs.approver_id', 'left')
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
        return $this->update($submissionId, [
            'status' => 'approved',
            'approver_id' => $approverId,
            'approved_at' => date('Y-m-d H:i:s'),
            'approval_comments' => $comments,
            'signature_applied' => 1
        ]);
    }
    
    /**
     * Reject a submission
     */
    public function rejectSubmission($submissionId, $approverId, $reason = '')
    {
        return $this->update($submissionId, [
            'status' => 'rejected',
            'approver_id' => $approverId,
            'approved_at' => date('Y-m-d H:i:s'),
            'rejected_reason' => $reason
        ]);
    }
    
    /**
     * Mark a submission as serviced
     */
    public function markAsServiced($submissionId, $serviceStaffId, $notes = '')
    {
        return $this->update($submissionId, [
            'service_staff_id' => $serviceStaffId,
            'service_staff_signature_date' => date('Y-m-d H:i:s'),
            'service_notes' => $notes,
            'status' => 'completed'
        ]);
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
}
