<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHeaderImageToForms extends Migration
{
    public function up()
    {
        // Add header_image column to forms table
        $this->forge->addColumn('forms', [
            'header_image' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'panel_name',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('forms', 'header_image');
    }
}
