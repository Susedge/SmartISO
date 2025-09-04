<?php

namespace App\Models;

use CodeIgniter\Model;

class FeedbackModel extends Model
{
    protected $table      = 'feedback';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;
    
    protected $returnType     = 'array';
    
    protected $allowedFields = [
        'submission_id', 'user_id', 'rating', 'comments', 'service_quality',
        'timeliness', 'staff_professionalism', 'overall_satisfaction',
        'suggestions', 'status'
    ];
    
    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
    // Validation
    protected $validationRules = [
        'submission_id'         => 'required|integer',
        'user_id'              => 'required|integer',
        'rating'               => 'required|integer|greater_than[0]|less_than[6]',
        'service_quality'      => 'permit_empty|integer|greater_than[0]|less_than[6]',
        'timeliness'           => 'permit_empty|integer|greater_than[0]|less_than[6]',
        'staff_professionalism' => 'permit_empty|integer|greater_than[0]|less_than[6]',
        'overall_satisfaction' => 'permit_empty|integer|greater_than[0]|less_than[6]',
        'status'               => 'permit_empty|in_list[pending,reviewed,addressed]'
    ];

    /**
     * Get feedback with submission details
     */
    public function getFeedbackWithDetails($userId = null)
    {
        $builder = $this->db->table('feedback f');
        $builder->select('f.*, fs.form_id, fs.panel_name,
                          form.code as form_code, form.description as form_description,
                          u.full_name as user_name')
            ->join('form_submissions fs', 'fs.id = f.submission_id', 'left')
            ->join('forms form', 'form.id = fs.form_id', 'left')
            ->join('users u', 'u.id = f.user_id', 'left');
        
        if ($userId) {
            $builder->where('f.user_id', $userId);
        }
        
        $builder->orderBy('f.created_at', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get average ratings for a specific form or overall
     */
    public function getAverageRatings($formId = null)
    {
        $builder = $this->db->table('feedback f');
        
        if ($formId) {
            $builder->join('form_submissions fs', 'fs.id = f.submission_id')
                   ->where('fs.form_id', $formId);
        }
        
        $builder->select('
            AVG(f.rating) as avg_overall_rating,
            AVG(f.service_quality) as avg_service_quality,
            AVG(f.timeliness) as avg_timeliness,
            AVG(f.staff_professionalism) as avg_staff_professionalism,
            AVG(f.overall_satisfaction) as avg_overall_satisfaction,
            COUNT(*) as total_feedback
        ');
        
        return $builder->get()->getRowArray();
    }

    /**
     * Get feedback requiring review
     */
    public function getPendingReview()
    {
        $builder = $this->db->table('feedback f');
        $builder->select('f.*, fs.form_id, fs.panel_name,
                          form.code as form_code, form.description as form_description,
                          u.full_name as user_name')
            ->join('form_submissions fs', 'fs.id = f.submission_id', 'left')
            ->join('forms form', 'form.id = fs.form_id', 'left')
            ->join('users u', 'u.id = f.user_id', 'left')
            ->where('f.status', 'pending')
            ->orWhere('f.rating <=', 2)
            ->orderBy('f.created_at', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Check if user already provided feedback for a submission
     */
    public function hasFeedback($submissionId, $userId)
    {
        return $this->getFeedbackBySubmissionAndUser($submissionId, $userId) !== null;
    }

    /**
     * Get a single feedback record for a specific submission & user
     * Returns array|null
     */
    public function getFeedbackBySubmissionAndUser($submissionId, $userId)
    {
        return $this->where('submission_id', $submissionId)
                    ->where('user_id', $userId)
                    ->first();
    }
}
