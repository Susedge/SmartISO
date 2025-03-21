<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldsToDbpanel extends Migration
{
    public function up()
    {
        // Add required and width columns to the dbpanel table if they don't exist
        if (!$this->db->fieldExists('required', 'dbpanel')) {
            $this->forge->addColumn('dbpanel', [
                'required' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'bump_next_field'
                ]
            ]);
        }
        
        if (!$this->db->fieldExists('width', 'dbpanel')) {
            $this->forge->addColumn('dbpanel', [
                'width' => [
                    'type' => 'INT',
                    'constraint' => 2,
                    'default' => 6,
                    'after' => 'required'
                ]
            ]);
        }
    }

    public function down()
    {
        // Drop the columns if they exist
        if ($this->db->fieldExists('required', 'dbpanel')) {
            $this->forge->dropColumn('dbpanel', 'required');
        }
        
        if ($this->db->fieldExists('width', 'dbpanel')) {
            $this->forge->dropColumn('dbpanel', 'width');
        }
    }
}
