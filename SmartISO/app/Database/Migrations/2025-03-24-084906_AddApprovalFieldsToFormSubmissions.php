<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddServiceColumnsToFormSubmissions extends Migration
{
    public function up()
    {
        $fields = [];
        
        // Add service_staff_id if it doesn't exist
        if (!$this->db->fieldExists('service_staff_id', 'form_submissions')) {
            $fields['service_staff_id'] = [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ];
        }
        
        // Add service_staff_signature_date if it doesn't exist
        if (!$this->db->fieldExists('service_staff_signature_date', 'form_submissions')) {
            $fields['service_staff_signature_date'] = [
                'type' => 'DATETIME',
                'null' => true,
            ];
        }
        
        // Add service_notes if it doesn't exist
        if (!$this->db->fieldExists('service_notes', 'form_submissions')) {
            $fields['service_notes'] = [
                'type' => 'TEXT',
                'null' => true,
            ];
        }
        
        // Add requestor_signature_date if it doesn't exist
        if (!$this->db->fieldExists('requestor_signature_date', 'form_submissions')) {
            $fields['requestor_signature_date'] = [
                'type' => 'DATETIME',
                'null' => true,
            ];
        }
        
        // Add completed if it doesn't exist
        if (!$this->db->fieldExists('completed', 'form_submissions')) {
            $fields['completed'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ];
        }
        
        // Add completion_date if it doesn't exist
        if (!$this->db->fieldExists('completion_date', 'form_submissions')) {
            $fields['completion_date'] = [
                'type' => 'DATETIME',
                'null' => true,
            ];
        }
        
        // Only add columns if we have fields to add
        if (!empty($fields)) {
            $this->forge->addColumn('form_submissions', $fields);
        }
    }

    public function down()
    {
        // Remove fields if they exist
        if ($this->db->fieldExists('service_staff_id', 'form_submissions')) {
            $this->forge->dropColumn('form_submissions', 'service_staff_id');
        }
        
        if ($this->db->fieldExists('service_staff_signature_date', 'form_submissions')) {
            $this->forge->dropColumn('form_submissions', 'service_staff_signature_date');
        }
        
        if ($this->db->fieldExists('service_notes', 'form_submissions')) {
            $this->forge->dropColumn('form_submissions', 'service_notes');
        }
        
        if ($this->db->fieldExists('requestor_signature_date', 'form_submissions')) {
            $this->forge->dropColumn('form_submissions', 'requestor_signature_date');
        }
        
        if ($this->db->fieldExists('completed', 'form_submissions')) {
            $this->forge->dropColumn('form_submissions', 'completed');
        }
        
        if ($this->db->fieldExists('completion_date', 'form_submissions')) {
            $this->forge->dropColumn('form_submissions', 'completion_date');
        }
    }
}
