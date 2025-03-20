<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFormSubmissionsTable extends Migration
{
    public function up()
    {
        // Main submissions table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'form_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'panel_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'submitted_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'submitted',
                'comment' => 'submitted, approved, rejected',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey(['form_id', 'submitted_by']);
        $this->forge->createTable('form_submissions');
        
        // Form data details table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'submission_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'field_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'field_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey(['submission_id', 'field_name']);
        $this->forge->createTable('form_submission_data');
    }

    public function down()
    {
        $this->forge->dropTable('form_submission_data');
        $this->forge->dropTable('form_submissions');
    }
}
