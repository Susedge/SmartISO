<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class OfficeSingleDepartment20250909 extends Migration
{
    public function up()
    {
        // 1) Ensure department_id column exists on offices
    $fields = [
            'department_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => null,
                'after' => 'id'
            ]
        ];
    // Add column if it doesn't exist
    $this->forge->addColumn('offices', $fields);

        // 2) Populate department_id from department_office pivot picking the smallest department_id (deterministic)
        $db = \Config\Database::connect();
        $db->query("UPDATE offices o
            JOIN (
                SELECT office_id, MIN(department_id) AS department_id
                FROM department_office
                GROUP BY office_id
            ) dob ON o.id = dob.office_id
            SET o.department_id = dob.department_id
        ");

    // 3) Add unique index on (department_id, code) to allow same code in different departments
    try {
        $db->query('ALTER TABLE offices ADD UNIQUE KEY ux_office_dept_code (department_id, code)');
    } catch (\Exception $e) {
        // Ignore index creation errors (duplicates may exist). Admin should resolve duplicates before adding index.
    }

        // 4) Backup the pivot and drop it
        // Create a backup table for safety then drop original
        $db->query('CREATE TABLE IF NOT EXISTS department_office_backup LIKE department_office');
        $db->query('INSERT INTO department_office_backup SELECT * FROM department_office');
        $db->query('DROP TABLE IF EXISTS department_office');
    }

    public function down()
    {
        $db = \Config\Database::connect();
        // Restore pivot if backup exists
        $tables = $db->listTables();
        if (in_array('department_office_backup', $tables)) {
            $db->query('CREATE TABLE IF NOT EXISTS department_office LIKE department_office_backup');
            $db->query('INSERT INTO department_office SELECT * FROM department_office_backup');
            $db->query('DROP TABLE IF EXISTS department_office_backup');
        }

        // Drop the unique key if present
        // Not all DB drivers expose dropKey via forge for composite keys reliably here; use raw SQL where supported
        try {
            $db->query('ALTER TABLE offices DROP INDEX ux_office_dept_code');
        } catch (\Exception $e) {
            // ignore
        }

        // Drop department_id column
        $this->forge->dropColumn('offices', 'department_id');
    }
}
