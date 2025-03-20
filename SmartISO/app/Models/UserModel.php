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
        'department_id', 'user_type', 'active', 'reset_token', 
        'reset_expires', 'last_login'
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
        'user_type'     => 'required|in_list[user,admin,superuser]',
    ];
    
    /**
     * Finds a user by their email address
     */
    public function findByEmail(string $email)
    {
        return $this->where('email', $email)->first();
    }
    
    /**
     * Finds a user by their username
     */
    public function findByUsername(string $username)
    {
        return $this->where('username', $username)->first();
    }
    
    /**
     * Sets a user's password
     */
    public function setPassword(int $userId, string $password)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        return $this->update($userId, ['password_hash' => $hash]);
    }
}
