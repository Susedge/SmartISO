<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'user_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Action type: create, update, delete, login, logout, view, approve, reject, etc.',
            ],
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Entity type: user, form, submission, panel, department, office, config, etc.',
            ],
            'entity_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'entity_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Human-readable name of the entity',
            ],
            'old_values' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON of old values before change',
            ],
            'new_values' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON of new values after change',
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'Human-readable description of the action',
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('action');
        $this->forge->addKey('entity_type');
        $this->forge->addKey('entity_id');
        $this->forge->addKey('created_at');
        
        // Composite index for common queries
        $this->forge->addKey(['entity_type', 'entity_id'], false, false, 'idx_entity');
        $this->forge->addKey(['user_id', 'created_at'], false, false, 'idx_user_time');
        
        $this->forge->createTable('audit_logs');
    }

    public function down()
    {
        $this->forge->dropTable('audit_logs');
    }
}
