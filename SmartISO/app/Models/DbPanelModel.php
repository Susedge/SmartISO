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
        'required', 'width', 'field_role', 'default_value',
        'department_id', 'office_id'
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
                // Default to requestor per new requirement
                $row['field_role'] = 'requestor';
            }
        }

        // If default_value key is not present, ensure it exists to avoid DB errors
        if (is_array($row) && !array_key_exists('default_value', $row)) {
            if ($this->db->fieldExists('default_value', $this->table)) {
                $row['default_value'] = '';
            }
        }
        
        return parent::save($row);
    }
    
    public function getPanels()
    {
        $db = \Config\Database::connect();
        
        return $db->table($this->table . ' p')
                    ->select('p.panel_name, p.department_id, p.office_id, d.description as department_name, o.description as office_name')
                    ->join('departments d', 'd.id = p.department_id', 'left')
                    ->join('offices o', 'o.id = p.office_id', 'left')
                    ->distinct()
                    ->where('p.panel_name IS NOT NULL')
                    ->where('p.panel_name !=', '')
                    ->orderBy('p.panel_name', 'ASC')
                    ->get()
                    ->getResultArray();
    }
}
