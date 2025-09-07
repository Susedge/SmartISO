<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDepartmentIdToFormsTable extends Migration
{
    public function up()
    {
        // Add department_id column if it doesn't exist
        if (!$this->db->fieldExists('department_id', 'forms')) {
            $this->forge->addColumn('forms', [
                'department_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'after'      => 'office_id'
                ],
            ]);
        }

        // Populate departments table from existing offices if departments empty or missing codes
        // Offices are becoming Departments (initial mapping)
        $offices    = $this->db->table('offices')->get()->getResultArray();
        $deptTable  = $this->db->table('departments');
        $existingDepts = $deptTable->get()->getResultArray();
        $existingCodes = array_column($existingDepts, 'code');

        foreach ($offices as $office) {
            if (!in_array($office['code'], $existingCodes, true)) {
                $deptTable->insert([
                    'code'        => $office['code'],
                    'description' => $office['description'],
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
                $existingCodes[] = $office['code'];
            }
        }

        // Build code=>id map for departments
        $deptMap = [];
        $departments = $deptTable->get()->getResultArray();
        foreach ($departments as $d) {
            $deptMap[$d['code']] = $d['id'];
        }

        // For each form with office_id, map department via matching office code
        if ($this->db->fieldExists('office_id', 'forms')) {
            $forms = $this->db->table('forms f')
                ->select('f.id, f.office_id, o.code AS office_code')
                ->join('offices o', 'o.id = f.office_id', 'left')
                ->get()->getResultArray();

            foreach ($forms as $form) {
                if (!empty($form['office_code']) && isset($deptMap[$form['office_code']])) {
                    $this->db->table('forms')->where('id', $form['id'])->update([
                        'department_id' => $deptMap[$form['office_code']]
                    ]);
                }
            }
        }

        // Also backfill users.department_id from users.office_id where department_id is NULL
        if ($this->db->fieldExists('office_id', 'users') && $this->db->fieldExists('department_id', 'users')) {
            $users = $this->db->table('users u')
                ->select('u.id, u.office_id, o.code AS office_code, u.department_id')
                ->join('offices o', 'o.id = u.office_id', 'left')
                ->where('u.department_id IS NULL')
                ->get()->getResultArray();

            foreach ($users as $user) {
                if (!empty($user['office_code']) && isset($deptMap[$user['office_code']])) {
                    $this->db->table('users')->where('id', $user['id'])->update([
                        'department_id' => $deptMap[$user['office_code']]
                    ]);
                }
            }
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('department_id', 'forms')) {
            $this->forge->dropColumn('forms', 'department_id');
        }
        // We intentionally do not remove populated departments to avoid data loss.
    }
}
