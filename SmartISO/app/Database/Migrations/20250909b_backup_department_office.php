<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackupDepartmentOffice20250909b extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Only proceed if department_office exists
        $tables = $db->listTables();
        if (!in_array('department_office', $tables)) {
            // nothing to do
            return;
        }

        // 1) Create backup table and copy data
        try {
            $db->query('CREATE TABLE IF NOT EXISTS department_office_backup LIKE department_office');
            $db->query('INSERT INTO department_office_backup SELECT * FROM department_office');
        } catch (\Exception $e) {
            // If backup fails, stop to avoid data loss
            throw $e;
        }

        // 2) For offices missing department_id, set department_id = MIN(department_id) from pivot
        try {
            $db->query("UPDATE offices o
                JOIN (
                    SELECT office_id, MIN(department_id) AS department_id
                    FROM department_office
                    GROUP BY office_id
                ) dob ON o.id = dob.office_id
                SET o.department_id = COALESCE(o.department_id, dob.department_id)");
        } catch (\Exception $e) {
            // non-fatal; log
        }

        // 3) Drop the pivot table now that data is backed up
        try {
            $db->query('DROP TABLE IF EXISTS department_office');
        } catch (\Exception $e) {
            // ignore
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $tables = $db->listTables();
        if (in_array('department_office_backup', $tables)) {
            // recreate original and restore data
            try {
                $db->query('CREATE TABLE IF NOT EXISTS department_office LIKE department_office_backup');
                $db->query('INSERT INTO department_office SELECT * FROM department_office_backup');
                $db->query('DROP TABLE IF EXISTS department_office_backup');
            } catch (\Exception $e) {
                // ignore
            }
        }
    }
}
