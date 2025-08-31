<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOfficeIdToFormsTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('forms', [
            'office_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'description',
            ],
        ]);

        // Add foreign key constraint
        $this->db->query('ALTER TABLE forms ADD CONSTRAINT fk_forms_office_id FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE SET NULL');
    }

    public function down()
    {
        // Drop foreign key constraint first
        $this->db->query('ALTER TABLE forms DROP FOREIGN KEY fk_forms_office_id');

        // Drop the column
        $this->forge->dropColumn('forms', 'office_id');
    }
}
