<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeDefaultValueToText extends Migration
{
    public function up()
    {
        // Change default_value column from VARCHAR(255) to TEXT to support longer JSON arrays
        if ($this->db->fieldExists('default_value', 'dbpanel')) {
            $this->forge->modifyColumn('dbpanel', [
                'default_value' => [
                    'type' => 'TEXT',
                    'null' => true
                ]
            ]);
            
            log_message('info', 'Changed dbpanel.default_value from VARCHAR(255) to TEXT');
        }
    }

    public function down()
    {
        // Revert back to VARCHAR(255) - WARNING: This will truncate data!
        if ($this->db->fieldExists('default_value', 'dbpanel')) {
            $this->forge->modifyColumn('dbpanel', [
                'default_value' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'default' => ''
                ]
            ]);
        }
    }
}
