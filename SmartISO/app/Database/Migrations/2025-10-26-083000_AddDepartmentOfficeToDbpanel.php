<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDepartmentOfficeToDbpanel extends Migration
{
    public function up()
    {
        // Add department_id and office_id columns to dbpanel table
        if (! $this->db->fieldExists('department_id', 'dbpanel')) {
            $fields = [
                'department_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'panel_name'
                ]
            ];
            $this->forge->addColumn('dbpanel', $fields);
        }

        if (! $this->db->fieldExists('office_id', 'dbpanel')) {
            $fields = [
                'office_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'department_id'
                ]
            ];
            $this->forge->addColumn('dbpanel', $fields);
        }

        // Add foreign keys
        try {
            $this->db->query('ALTER TABLE `dbpanel` 
                ADD CONSTRAINT `dbpanel_department_fk` 
                FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) 
                ON DELETE SET NULL ON UPDATE CASCADE');
        } catch (\Exception $e) {
            // Foreign key might already exist or constraint failed
        }

        try {
            $this->db->query('ALTER TABLE `dbpanel` 
                ADD CONSTRAINT `dbpanel_office_fk` 
                FOREIGN KEY (`office_id`) REFERENCES `offices`(`id`) 
                ON DELETE SET NULL ON UPDATE CASCADE');
        } catch (\Exception $e) {
            // Foreign key might already exist or constraint failed
        }
    }

    public function down()
    {
        // Drop foreign keys first
        try {
            $this->db->query('ALTER TABLE `dbpanel` DROP FOREIGN KEY `dbpanel_department_fk`');
        } catch (\Exception $e) {
            // Foreign key might not exist
        }

        try {
            $this->db->query('ALTER TABLE `dbpanel` DROP FOREIGN KEY `dbpanel_office_fk`');
        } catch (\Exception $e) {
            // Foreign key might not exist
        }

        // Drop columns
        if ($this->db->fieldExists('department_id', 'dbpanel')) {
            $this->forge->dropColumn('dbpanel', 'department_id');
        }

        if ($this->db->fieldExists('office_id', 'dbpanel')) {
            $this->forge->dropColumn('dbpanel', 'office_id');
        }
    }
}
