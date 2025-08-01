<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateConfigurationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'config_key' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'config_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'config_description' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'config_type' => [
                'type' => 'ENUM',
                'constraint' => ['string', 'integer', 'boolean', 'json'],
                'default' => 'string',
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
        $this->forge->addUniqueKey('config_key');
        $this->forge->createTable('configurations');
        
        // Insert default configurations
        $data = [
            [
                'config_key' => 'session_timeout',
                'config_value' => '30',
                'config_description' => 'Session timeout in minutes',
                'config_type' => 'integer',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'config_key' => 'system_name',
                'config_value' => 'SmartISO',
                'config_description' => 'System name displayed in the application',
                'config_type' => 'string',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'config_key' => 'enable_registration',
                'config_value' => '1',
                'config_description' => 'Allow new user registration (1 = enabled, 0 = disabled)',
                'config_type' => 'boolean',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
        
        $this->db->table('configurations')->insertBatch($data);
    }

    public function down()
    {
        $this->forge->dropTable('configurations');
    }
}
