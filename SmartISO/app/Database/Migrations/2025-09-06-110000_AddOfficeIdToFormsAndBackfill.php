<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOfficeIdToFormsAndBackfill extends Migration
{
    public function up()
    {
        // Add office_id to forms if missing
        if (!$this->db->fieldExists('office_id', 'forms')) {
            $this->forge->addColumn('forms', [
                'office_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'after'      => 'panel_name'
                ]
            ]);
        }

        // If offices now belong to departments, attempt a reasonable backfill where forms only have department_id
        $hasDept = $this->db->fieldExists('department_id', 'forms');
        if ($hasDept) {
            $forms = $this->db->table('forms')->select('id, office_id, department_id')->get()->getResultArray();
            if ($forms) {
                // Build department->first active office map
                $officeRows = $this->db->table('offices')->select('id, department_id, active')->get()->getResultArray();
                $deptOfficeMap = [];
                foreach ($officeRows as $or) {
                    if (!empty($or['department_id']) && (int)$or['active'] === 1) {
                        $deptOfficeMap[$or['department_id']] = $deptOfficeMap[$or['department_id']] ?? $or['id'];
                    }
                }
                foreach ($forms as $f) {
                    if (empty($f['office_id']) && !empty($f['department_id']) && isset($deptOfficeMap[$f['department_id']])) {
                        $this->db->table('forms')->where('id', $f['id'])->update([
                            'office_id' => $deptOfficeMap[$f['department_id']]
                        ]);
                    }
                }
            }
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('office_id', 'forms')) {
            $this->forge->dropColumn('forms', 'office_id');
        }
    }
}
