<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDefaultValueToDbpanel_v2 extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('default_value', 'dbpanel')) {
            $fields = [
                'default_value' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true,
                    'default' => ''
                ]
            ];
            $this->forge->addColumn('dbpanel', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('default_value', 'dbpanel')) {
            $this->forge->dropColumn('dbpanel', 'default_value');
        }
    }
}
