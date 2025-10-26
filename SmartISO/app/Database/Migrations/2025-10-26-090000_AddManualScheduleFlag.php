<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddManualScheduleFlag extends Migration
{
    public function up()
    {
        $fields = [
            'is_manual_schedule' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
                'comment' => '1 if schedule was manually set (not auto-generated from priority), 0 otherwise',
                'after' => 'priority_level'
            ]
        ];
        
        $this->forge->addColumn('schedules', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('schedules', 'is_manual_schedule');
    }
}
