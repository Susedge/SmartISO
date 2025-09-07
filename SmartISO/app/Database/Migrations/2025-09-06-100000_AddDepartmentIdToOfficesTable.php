<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDepartmentIdToOfficesTable extends Migration
{
    public function up()
    {
        // Add department_id column if not exists
        if (!$this->db->fieldExists('department_id', 'offices')) {
            $this->forge->addColumn('offices', [
                'department_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'after'      => 'description'
                ]
            ]);
            // Add FK if departments table exists
            try {
                $this->db->query('ALTER TABLE offices ADD CONSTRAINT fk_offices_department_id FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL');
            } catch (\Throwable $e) {
                log_message('warning', 'Could not add foreign key fk_offices_department_id: ' . $e->getMessage());
            }
        }

        // Deactivate existing offices so admins explicitly reassign under departments
        try {
            if ($this->db->fieldExists('active', 'offices')) {
                $this->db->query('UPDATE offices SET active = 0');
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Failed to deactivate existing offices: ' . $e->getMessage());
        }
    }

    public function down()
    {
        // Remove foreign key and column if present
        try {
            $this->db->query('ALTER TABLE offices DROP FOREIGN KEY fk_offices_department_id');
        } catch (\Throwable $e) {
            // Ignore if not exists
        }
        if ($this->db->fieldExists('department_id', 'offices')) {
            $this->forge->dropColumn('offices', 'department_id');
        }
    }
}
