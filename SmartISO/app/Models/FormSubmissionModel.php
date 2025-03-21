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
        'form_id', 'panel_name', 'submitted_by', 'status'
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
        
        // Filter by user if needed
        if ($userId !== null) {
            $builder->where('fs.submitted_by', $userId);
        }
        
        // Order by creation date
        $builder->orderBy('fs.created_at', 'DESC');
        
        // Get the results
        return $builder->get()->getResultArray();
    }
    
}
