<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStaffAvailabilityTable extends Migration
{
    public function up()
    {
        // Staff Availability table - stores when staff members are available/unavailable
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'staff_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'start_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'end_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'availability_type' => [
                'type'       => 'ENUM',
                'constraint' => ['available', 'busy', 'leave', 'holiday'],
                'default'    => 'available',
            ],
            'notes' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('staff_id');
        $this->forge->addKey('date');
        $this->forge->addKey(['staff_id', 'date'], false, false, 'idx_staff_date');
        
        $this->forge->createTable('staff_availability');
    }

    public function down()
    {
        $this->forge->dropTable('staff_availability');
    }
}
