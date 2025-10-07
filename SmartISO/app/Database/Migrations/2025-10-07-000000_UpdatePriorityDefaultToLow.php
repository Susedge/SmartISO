<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdatePriorityDefaultToLow extends Migration
{
    public function up()
    {
        // Modify the priority column to change default from 'normal' to 'low'
        $fields = [
            'priority' => [
                'type' => 'ENUM',
                'constraint' => ['low', 'normal', 'high', 'urgent', 'critical'],
                'default' => 'low',
                'null' => false
            ]
        ];
        
        $this->forge->modifyColumn('form_submissions', $fields);
    }

    public function down()
    {
        // Revert back to 'normal' as default
        $fields = [
            'priority' => [
                'type' => 'ENUM',
                'constraint' => ['low', 'normal', 'high', 'urgent', 'critical'],
                'default' => 'normal',
                'null' => false
            ]
        ];
        
        $this->forge->modifyColumn('form_submissions', $fields);
    }
}
