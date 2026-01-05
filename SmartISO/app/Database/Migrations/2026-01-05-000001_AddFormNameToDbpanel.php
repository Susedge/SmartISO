<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFormNameToDbpanel extends Migration
{
    public function up()
    {
        // Add form_name column to dbpanel table if it doesn't exist
        if (!$this->db->fieldExists('form_name', 'dbpanel')) {
            $fields = [
                'form_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'after' => 'panel_name',
                    'comment' => 'Optional form code/name for grouping panels'
                ]
            ];
            $this->forge->addColumn('dbpanel', $fields);
        }
    }

    public function down()
    {
        // Remove form_name column if it exists
        if ($this->db->fieldExists('form_name', 'dbpanel')) {
            $this->forge->dropColumn('dbpanel', 'form_name');
        }
    }
}
