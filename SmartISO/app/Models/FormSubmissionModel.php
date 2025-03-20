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
        $builder = $this->db->table('form_submissions fs')
            ->select('fs.*, f.code as form_code, f.description as form_description, u.full_name as submitted_by_name')
            ->join('forms f', 'f.id = fs.form_id')
            ->join('users u', 'u.id = fs.submitted_by');
            
        if ($userId !== null) {
            $builder->where('fs.submitted_by', $userId);
        }
        
        return $builder->orderBy('fs.created_at', 'DESC')
                      ->get()
                      ->getResultArray();
    }    
}
