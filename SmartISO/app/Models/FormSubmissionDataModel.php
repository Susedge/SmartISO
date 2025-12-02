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
    
    // Disable soft deletes - table doesn't have deleted_at column
    protected $useSoftDeletes = false;
    
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
     * Get a specific field value for a submission
     */
    public function getFieldValue(int $submissionId, string $fieldName)
    {
        $record = $this->where('submission_id', $submissionId)
                       ->where('field_name', $fieldName)
                       ->first();
        
        return $record ? $record['field_value'] : null;
    }

    /**
     * Upsert a single field value for a submission (used for priority_level updates on calendar UI)
     */
    public function setFieldValue(int $submissionId, string $fieldName, $value): bool
    {
        $existing = $this->where('submission_id', $submissionId)
                         ->where('field_name', $fieldName)
                         ->first();
        
        if ($existing) {
            // Update existing record - only update the field_value
            $result = $this->update($existing['id'], ['field_value' => (string)$value]);
            log_message('info', "FormSubmissionDataModel::setFieldValue - Updated existing record ID {$existing['id']} for submission {$submissionId}, field {$fieldName}: " . ($result ? 'success' : 'failed'));
            return (bool)$result;
        }
        
        // Insert new record with all fields
        $data = [
            'submission_id' => $submissionId,
            'field_name' => $fieldName,
            'field_value' => (string)$value
        ];
        $result = $this->insert($data, true);
        log_message('info', "FormSubmissionDataModel::setFieldValue - Inserted new record for submission {$submissionId}, field {$fieldName}: " . ($result ? 'success' : 'failed'));
        return (bool)$result;
    }
}
