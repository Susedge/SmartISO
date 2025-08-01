<?php

namespace App\Models;

use CodeIgniter\Model;

class ConfigurationModel extends Model
{
    protected $table            = 'configurations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['config_key', 'config_value', 'config_description', 'config_type'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'config_key' => 'required|max_length[100]',
        'config_value' => 'permit_empty',
        'config_description' => 'permit_empty|max_length[255]',
        'config_type' => 'required|in_list[string,integer,boolean,json]'
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
    
    /**
     * Get configuration value by key
     */
    public function getConfig($key, $default = null)
    {
        $config = $this->where('config_key', $key)->first();
        
        if (!$config) {
            return $default;
        }
        
        // Cast value based on type
        switch ($config['config_type']) {
            case 'integer':
                return (int)$config['config_value'];
            case 'boolean':
                return (bool)$config['config_value'];
            case 'json':
                return json_decode($config['config_value'], true);
            default:
                return $config['config_value'];
        }
    }
    
    /**
     * Set configuration value
     */
    public function setConfig($key, $value, $description = null, $type = 'string')
    {
        $existing = $this->where('config_key', $key)->first();
        
        // Convert value based on type
        switch ($type) {
            case 'boolean':
                $value = $value ? '1' : '0';
                break;
            case 'json':
                $value = json_encode($value);
                break;
            default:
                $value = (string)$value;
        }
        
        $data = [
            'config_key' => $key,
            'config_value' => $value,
            'config_type' => $type
        ];
        
        if ($description) {
            $data['config_description'] = $description;
        }
        
        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            return $this->insert($data);
        }
    }
}
