<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEtaAndPriorityLevelToSchedules extends Migration
{
    public function up()
    {
        $fields = [
            'eta_days' => [
                'type' => 'INT',
                'constraint' => 3,
                'null' => true,
                'default' => null,
            ],
            'estimated_date' => [
                'type' => 'DATE',
                'null' => true,
                'default' => null,
            ],
            'priority_level' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
                'null' => true,
                'default' => null,
            ],
        ];

        $this->forge->addColumn('schedules', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('schedules', 'eta_days');
        $this->forge->dropColumn('schedules', 'estimated_date');
        $this->forge->dropColumn('schedules', 'priority_level');
    }
}
