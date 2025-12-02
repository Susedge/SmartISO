<?php

namespace App\Models;

use CodeIgniter\Model;

class OfficeModel extends Model
{
    protected $table      = 'offices';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;
    
    protected $returnType     = 'array';
    
    protected $allowedFields = ['code', 'description', 'active', 'department_id'];
    
    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
    // Disable soft deletes - table doesn't have deleted_at column
    protected $useSoftDeletes = false;
    
    // Validation
    protected $validationRules      = [
        'code'          => 'required|alpha_numeric|min_length[2]|max_length[20]|is_unique[offices.code,id,{id}]',
        'description'   => 'required|min_length[3]|max_length[255]',
        'active'        => 'permit_empty|in_list[0,1]',
        'department_id' => 'permit_empty|integer'
    ];

    /**
     * Get all active offices
     */
    public function getActiveOffices()
    {
        return $this->where('active', 1)->findAll();
    }

    /**
     * Get office by code
     */
    public function getByCode($code)
    {
        return $this->where('code', $code)->first();
    }

    /**
     * Get offices by department (new relationship)
     */
    public function getByDepartment($departmentId)
    {
        return $this->where('department_id', $departmentId)->findAll();
    }
}
