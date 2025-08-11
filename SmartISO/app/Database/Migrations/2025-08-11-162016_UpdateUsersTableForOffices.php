<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateUsersTableForOffices extends Migration
{
    public function up()
    {
        // Add office_id field
        $this->forge->addColumn('users', [
            'office_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'department_id',
            ],
        ]);
        
        // Copy department_id values to office_id (if you want to preserve existing data)
        $this->db->query('UPDATE users SET office_id = department_id WHERE department_id IS NOT NULL');
    }

    public function down()
    {
        // Remove office_id field
        $this->forge->dropColumn('users', 'office_id');
    }
}
