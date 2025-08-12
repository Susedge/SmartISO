<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnsureApproverIdExists extends Migration
{
    public function up()
    {
        // Check if approver_id field exists, if not add it
        if (!$this->db->fieldExists('approver_id', 'form_submissions')) {
            $this->forge->addColumn('form_submissions', [
                'approver_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'status'
                ]
            ]);
        }
        
        // Check if approved_at field exists, if not add it
        if (!$this->db->fieldExists('approved_at', 'form_submissions')) {
            $this->forge->addColumn('form_submissions', [
                'approved_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'approver_id'
                ]
            ]);
        }
        
        // Check if approval_comments field exists, if not add it
        if (!$this->db->fieldExists('approval_comments', 'form_submissions')) {
            $this->forge->addColumn('form_submissions', [
                'approval_comments' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'approved_at'
                ]
            ]);
        }
        
        // Check if rejected_reason field exists, if not add it
        if (!$this->db->fieldExists('rejected_reason', 'form_submissions')) {
            $this->forge->addColumn('form_submissions', [
                'rejected_reason' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'approval_comments'
                ]
            ]);
        }
        
        // Check if signature_applied field exists, if not add it
        if (!$this->db->fieldExists('signature_applied', 'form_submissions')) {
            $this->forge->addColumn('form_submissions', [
                'signature_applied' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'rejected_reason'
                ]
            ]);
        }
        
        // Check if service_staff_id field exists, if not add it
        if (!$this->db->fieldExists('service_staff_id', 'form_submissions')) {
            $this->forge->addColumn('form_submissions', [
                'service_staff_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'signature_applied'
                ]
            ]);
        }
        
        // Check if service_staff_signature_date field exists, if not add it
        if (!$this->db->fieldExists('service_staff_signature_date', 'form_submissions')) {
            $this->forge->addColumn('form_submissions', [
                'service_staff_signature_date' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'service_staff_id'
                ]
            ]);
        }
        
        // Check if service_notes field exists, if not add it
        if (!$this->db->fieldExists('service_notes', 'form_submissions')) {
            $this->forge->addColumn('form_submissions', [
                'service_notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'service_staff_signature_date'
                ]
            ]);
        }
        
        // Check if requestor_signature_date field exists, if not add it
        if (!$this->db->fieldExists('requestor_signature_date', 'form_submissions')) {
            $this->forge->addColumn('form_submissions', [
                'requestor_signature_date' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'service_notes'
                ]
            ]);
        }
        
        // Check if completed field exists, if not add it
        if (!$this->db->fieldExists('completed', 'form_submissions')) {
            $this->forge->addColumn('form_submissions', [
                'completed' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'requestor_signature_date'
                ]
            ]);
        }
        
        // Check if completion_date field exists, if not add it
        if (!$this->db->fieldExists('completion_date', 'form_submissions')) {
            $this->forge->addColumn('form_submissions', [
                'completion_date' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'completed'
                ]
            ]);
        }
    }

    public function down()
    {
        // Remove all the fields if they exist
        $fields = [
            'approver_id', 'approved_at', 'approval_comments', 'rejected_reason', 
            'signature_applied', 'service_staff_id', 'service_staff_signature_date', 
            'service_notes', 'requestor_signature_date', 'completed', 'completion_date'
        ];
        
        foreach ($fields as $field) {
            if ($this->db->fieldExists($field, 'form_submissions')) {
                $this->forge->dropColumn('form_submissions', $field);
            }
        }
    }
}
