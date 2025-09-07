<?php

namespace App\Models;

use CodeIgniter\Model;

class FormSubmissionDataModel extends Model
{
    protected $table      = 'form_submission_data';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    
    protected $allowedFields = [
        'submission_id', 'field_name', 'field_value'
    ];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
    /**
     * Get all field data for a submission
     */
    public function getSubmissionData($submissionId)
    {
        return $this->where('submission_id', $submissionId)
                   ->findAll();
    }
    
    /**
     * Get submission data as key-value pairs
     */
    public function getSubmissionDataAsArray($submissionId)
    {
        $data = $this->getSubmissionData($submissionId);
        $result = [];
        
        foreach ($data as $item) {
            $result[$item['field_name']] = $item['field_value'];
        }
        
        return $result;
    }

    /**
     * Upsert a single field value for a submission (used for priority_level updates on calendar UI)
     */
    public function setFieldValue(int $submissionId, string $fieldName, $value): bool
    {
        $existing = $this->where('submission_id', $submissionId)
                         ->where('field_name', $fieldName)
                         ->first();
        $data = [
            'submission_id' => $submissionId,
            'field_name' => $fieldName,
            'field_value' => (string)$value
        ];
        if ($existing) {
            return (bool)$this->update($existing['id'], $data);
        }
        return (bool)$this->insert($data, true);
    }
}
