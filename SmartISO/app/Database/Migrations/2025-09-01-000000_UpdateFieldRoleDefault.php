<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateFieldRoleDefault extends Migration
{
    public function up()
    {
        // Change default of field_role to 'requestor' if column exists
        if ($this->db->fieldExists('field_role', 'dbpanel')) {
            $this->forge->modifyColumn('dbpanel', [
                'field_role' => [
                    'name' => 'field_role',
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                    'default' => 'requestor',
                    'after' => 'width'
                ]
            ]);
        }
    }

    public function down()
    {
        // Revert default to 'both'
        if ($this->db->fieldExists('field_role', 'dbpanel')) {
            $this->forge->modifyColumn('dbpanel', [
                'field_role' => [
                    'name' => 'field_role',
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                    'default' => 'both',
                    'after' => 'width'
                ]
            ]);
        }
    }
}
