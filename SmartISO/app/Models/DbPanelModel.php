<?php

namespace App\Models;

use CodeIgniter\Model;

class DbpanelModel extends Model
{
    protected $table      = 'dbpanel';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    
    protected $allowedFields = [
        'panel_name', 'field_name', 'field_label', 'field_type', 
        'bump_next_field', 'code_table', 'length', 'field_order',
        'required', 'width', 'field_role'
    ];    
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
    /**
     * Get all fields for a specific panel
     */
    public function getPanelFields($panelName)
    {
        return $this->where('panel_name', $panelName)
                    ->orderBy('field_order', 'ASC')
                    ->findAll();
    }
    
    /**
     * Get all unique panel names
     */
    public function getPanelNames()
    {
        return $this->distinct()
                    ->select('panel_name')
                    ->findAll();
    }
}
