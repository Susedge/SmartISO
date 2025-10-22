<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;
    
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'email', 'username', 'password_hash', 'full_name', 
        'department_id', 'office_id', 'user_type', 'active', 'reset_token', 
        'reset_expires', 'last_login', 'signature', 'profile_image'
    ];
    
    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
    // Validation
    protected $validationRules = [
        'email'         => 'required|valid_email|is_unique[users.email,id,{id}]',
        'username'      => 'required|alpha_numeric_punct|min_length[3]|max_length[30]|is_unique[users.username,id,{id}]',
        'password_hash' => 'required',
        'full_name'     => 'required|min_length[3]|max_length[100]',
        'department_id' => 'permit_empty|integer',
        'office_id'     => 'permit_empty|integer',
        'user_type'     => 'required|in_list[admin,requestor,approving_authority,service_staff,superuser,department_admin]',
    ];
    
    /**
     * Get users by type
     */
    public function getUsersByType($type)
    {
        return $this->where('user_type', $type)->findAll();
    }

    /**
     * Get users with office information
     */
    public function getUsersWithOffice()
    {
        return $this->db->table('users u')
                       ->select('u.*, o.code as office_code, o.description as office_name')
                       ->join('offices o', 'o.id = u.office_id', 'left')
                       ->get()
                       ->getResultArray();
    }

    public function getUsersWithDepartment()
    {
        return $this->db->table('users u')
            ->select('u.*, d.code as department_code, d.description as department_name')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->get()->getResultArray();
    }

    /**
     * Get users by office
     */
    public function getUsersByOffice($officeId)
    {
        return $this->where('office_id', $officeId)->findAll();
    }
    
    /**
     * Upload and set user signature
     */
    public function setSignature($userId, $signatureFile)
    {
        // Generate unique filename
        $newName = $userId . '_' . time() . '.png';
        
        // Move uploaded file
        $signatureFile->move(ROOTPATH . 'public/uploads/signatures', $newName);
        
        // Update user record
        return $this->update($userId, ['signature' => $newName]);
    }
}
