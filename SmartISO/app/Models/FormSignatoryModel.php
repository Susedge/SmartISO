<?php

namespace App\Models;

use CodeIgniter\Model;

class FormSignatoryModel extends Model
{
    protected $table      = 'form_signatories';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;
    
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'form_id', 'user_id', 'order_position'
    ];
    
    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
    // Validation
    protected $validationRules = [
        'form_id'       => 'required|integer|is_not_unique[forms.id]',
        'user_id'       => 'required|integer|is_not_unique[users.id]',
        'order_position' => 'permit_empty|integer'
    ];
    
    /**
     * Get signatories for a specific form with user details
     */
    public function getFormSignatories(int $formId)
    {
        return $this->select('form_signatories.*, users.full_name, users.email, users.username')
                    ->join('users', 'users.id = form_signatories.user_id')
                    ->where('form_signatories.form_id', $formId)
                    ->orderBy('form_signatories.order_position', 'ASC')
                    ->findAll();
    }
    
    /**
     * Get forms that can be signed by a specific user
     */
    public function getUserForms(int $userId)
    {
        return $this->select('form_signatories.*, forms.code, forms.description')
                    ->join('forms', 'forms.id = form_signatories.form_id')
                    ->where('form_signatories.user_id', $userId)
                    ->orderBy('forms.code', 'ASC')
                    ->findAll();
    }
}
