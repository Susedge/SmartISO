<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPriorityAndReferenceFeatures extends Migration
{
    public function up()
    {
        // Add priority field to form_submissions
        $this->forge->addColumn('form_submissions', [
            'priority' => [
                'type' => 'ENUM',
                'constraint' => ['low', 'normal', 'high', 'urgent', 'critical'],
                'default' => 'low',
                'after' => 'status'
            ],
            'reference_file' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'priority'
            ],
            'reference_file_original' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'reference_file'
            ]
        ]);
        
        // Create priority configurations table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'priority_level' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'priority_weight' => [
                'type' => 'INT',
                'constraint' => 3,
                'unsigned' => true,
            ],
            'priority_color' => [
                'type' => 'VARCHAR',
                'constraint' => 7,
                'default' => '#6c757d'
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'sla_hours' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Service Level Agreement in hours'
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
        $this->forge->addUniqueKey('priority_level');
        $this->forge->createTable('priority_configurations');
        
        // Insert default priority levels
        $data = [
            [
                'priority_level' => 'low',
                'priority_weight' => 1,
                'priority_color' => '#28a745',
                'description' => 'Low priority - routine requests',
                'sla_hours' => 168, // 1 week
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'priority_level' => 'normal',
                'priority_weight' => 2,
                'priority_color' => '#6c757d',
                'description' => 'Normal priority - standard requests',
                'sla_hours' => 72, // 3 days
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'priority_level' => 'high',
                'priority_weight' => 3,
                'priority_color' => '#ffc107',
                'description' => 'High priority - important requests',
                'sla_hours' => 24, // 1 day
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'priority_level' => 'urgent',
                'priority_weight' => 4,
                'priority_color' => '#fd7e14',
                'description' => 'Urgent priority - time-sensitive requests',
                'sla_hours' => 4, // 4 hours
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'priority_level' => 'critical',
                'priority_weight' => 5,
                'priority_color' => '#dc3545',
                'description' => 'Critical priority - emergency requests',
                'sla_hours' => 1, // 1 hour
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->db->table('priority_configurations')->insertBatch($data);
    }

    public function down()
    {
        // Drop priority configurations table
        $this->forge->dropTable('priority_configurations');
        
        // Remove columns from form_submissions
        $this->forge->dropColumn('form_submissions', ['priority', 'reference_file', 'reference_file_original']);
    }
}
