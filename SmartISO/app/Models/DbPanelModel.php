<?php

namespace App\Models;

use CodeIgniter\Model;

class DbpanelModel extends Model
{
    /**
     * Rename a panel by updating all rows with old panel name to new panel name
     */
    public function renamePanel($oldName, $newName)
    {
        return $this->where('panel_name', $oldName)
            ->set(['panel_name' => $newName])
            ->update();
    }
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
    
    /**
     * Override save method to provide default values for field_role if column exists
     */
    public function save($row): bool
    {
        // If field_role is not set and the column exists, provide default
        if (is_array($row) && !isset($row['field_role'])) {
            // Check if field_role column exists in the table
            if ($this->db->fieldExists('field_role', $this->table)) {
                $row['field_role'] = 'both';
            }
        }
        
        return parent::save($row);
    }
    
    public function getPanels()
    {
        return $this->select('panel_name')
                    ->distinct()
                    ->where('panel_name IS NOT NULL')
                    ->where('panel_name !=', '')
                    ->orderBy('panel_name', 'ASC')
                    ->findAll();
    }
}
