<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnhanceFormSubmissionsForWorkflow extends Migration
{
    public function up()
    {
        $this->forge->addColumn('form_submissions', [
            'requestor_signature_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'status',
            ],
            'approver_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'requestor_signature_date',
            ],
            'approver_signature_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'approver_id',
            ],
            'service_staff_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'approver_signature_date',
            ],
            'service_staff_signature_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'service_staff_id',
            ],
            'completed' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'service_staff_signature_date',
            ],
            'completion_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'completed',
            ],
            'rejection_reason' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'completion_date',
            ],
        ]);

        // Add foreign key constraints
        $this->forge->addForeignKey('approver_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('service_staff_id', 'users', 'id', 'CASCADE', 'SET NULL');
    }

    public function down()
    {
        // Remove foreign keys first
        $this->db->disableForeignKeyChecks();
        
        // Drop columns
        $this->forge->dropColumn('form_submissions', [
            'requestor_signature_date',
            'approver_id', 
            'approver_signature_date',
            'service_staff_id',
            'service_staff_signature_date',
            'completed',
            'completion_date',
            'rejection_reason'
        ]);
        
        $this->db->enableForeignKeyChecks();
    }
}
