<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPanelActiveStatus extends Migration
{
    public function up()
    {
        // Add is_active column to dbpanel table
        // This allows marking which panel version is currently active
        // Only active panels will be available for new form submissions
        $this->forge->addColumn('dbpanel', [
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'null' => false,
                'after' => 'default_value'
            ]
        ]);
        
        // Add index for faster queries on active panels
        $this->db->query('CREATE INDEX idx_dbpanel_active ON dbpanel(panel_name, is_active)');
    }

    public function down()
    {
        // Remove index first
        $this->db->query('DROP INDEX idx_dbpanel_active ON dbpanel');
        
        // Remove column
        $this->forge->dropColumn('dbpanel', 'is_active');
    }
}
