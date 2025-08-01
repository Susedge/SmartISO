<?php

namespace App\Models;

use CodeIgniter\Model;

class PriorityConfigurationModel extends Model
{
    protected $table = 'priority_configurations';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'priority_level', 'priority_weight', 'priority_color', 
        'description', 'sla_hours'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [
        'priority_level' => 'required|max_length[20]|is_unique[priority_configurations.priority_level,id,{id}]',
        'priority_weight' => 'required|integer|greater_than[0]',
        'priority_color' => 'required|max_length[7]',
        'sla_hours' => 'permit_empty|integer|greater_than[0]'
    ];
    
    /**
     * Get all priorities ordered by weight
     */
    public function getPrioritiesByWeight($ascending = false)
    {
        $order = $ascending ? 'ASC' : 'DESC';
        return $this->orderBy('priority_weight', $order)->findAll();
    }
    
    /**
     * Get priority configuration by level
     */
    public function getPriorityByLevel($level)
    {
        return $this->where('priority_level', $level)->first();
    }
    
    /**
     * Get priority options for dropdowns
     */
    public function getPriorityOptions()
    {
        $priorities = $this->orderBy('priority_weight', 'ASC')->findAll();
        $options = [];
        
        foreach ($priorities as $priority) {
            $options[$priority['priority_level']] = ucfirst($priority['priority_level']) . 
                ' (' . $priority['sla_hours'] . 'h SLA)';
        }
        
        return $options;
    }
}
