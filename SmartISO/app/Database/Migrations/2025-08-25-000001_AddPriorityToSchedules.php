<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPriorityToSchedules extends Migration
{
    public function up()
    {
        $fields = [
            'priority' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
                'after' => 'completion_notes'
            ]
        ];

        $this->forge->addColumn('schedules', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('schedules', 'priority');
    }
}
