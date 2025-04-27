<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldRoleToDbpanel extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dbpanel', [
            'field_role' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'both',
                'after' => 'width'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('dbpanel', 'field_role');
    }

    #test
}