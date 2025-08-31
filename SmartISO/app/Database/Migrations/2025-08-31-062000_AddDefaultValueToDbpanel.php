<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDefaultValueToDbpanel extends Migration
{
    public function up()
    {
        // Add default_value column to the dbpanel table if it doesn't exist
        if (! $this->db->fieldExists('default_value', 'dbpanel')) {
            $this->forge->addColumn('dbpanel', [
                'default_value' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'default' => '',
                    'after' => 'length'
                ]
            ]);
        }
    }

    public function down()
    {
        // Drop the column if it exists
        if ($this->db->fieldExists('default_value', 'dbpanel')) {
            $this->forge->dropColumn('dbpanel', 'default_value');
        }
    }
}
