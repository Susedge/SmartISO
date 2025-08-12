<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOfficesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
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
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('offices');
        
        // Run the seeder immediately after creating the table
        $seeder = \Config\Database::seeder();
        $seeder->call('OfficeSeeder');
    }

    public function down()
    {
        $this->forge->dropTable('offices');
    }
}
