<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDbpanelTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'panel_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'field_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'field_label' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'field_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'comment' => 'input, dropdown, textarea, datepicker',
            ],
            'bump_next_field' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'code_table' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'For dropdown options - table name to fetch options',
            ],
            'length' => [
                'type' => 'INT',
                'constraint' => 5,
                'null' => true,
            ],
            'field_order' => [
                'type' => 'INT',
                'constraint' => 5,
                'default' => 0,
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
        $this->forge->addKey(['panel_name', 'field_name']);
        $this->forge->createTable('dbpanel');
    }

    public function down()
    {
        $this->forge->dropTable('dbpanel');
    }
}
