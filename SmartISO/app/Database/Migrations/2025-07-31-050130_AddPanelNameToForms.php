<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPanelNameToForms extends Migration
{
    public function up()
    {
        $this->forge->addColumn('forms', [
            'panel_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'description'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('forms', 'panel_name');
    }
}
