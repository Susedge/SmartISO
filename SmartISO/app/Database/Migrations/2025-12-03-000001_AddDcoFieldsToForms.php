<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDcoFieldsToForms extends Migration
{
    public function up()
    {
        // Add DCO approval and revision fields to forms table
        $fields = [
            'revision_no' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => '00',
                'after' => 'header_image'
            ],
            'effectivity_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'revision_no'
            ],
            'dco_approved' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'effectivity_date'
            ],
            'dco_approved_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'dco_approved'
            ],
            'dco_approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'dco_approved_by'
            ],
        ];

        // Check and add each field only if it doesn't exist
        foreach ($fields as $fieldName => $fieldDef) {
            if (!$this->db->fieldExists($fieldName, 'forms')) {
                $this->forge->addColumn('forms', [$fieldName => $fieldDef]);
            }
        }
    }

    public function down()
    {
        // Remove the fields
        $fields = ['revision_no', 'effectivity_date', 'dco_approved', 'dco_approved_by', 'dco_approved_at'];
        
        foreach ($fields as $fieldName) {
            if ($this->db->fieldExists($fieldName, 'forms')) {
                $this->forge->dropColumn('forms', $fieldName);
            }
        }
    }
}
