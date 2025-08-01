<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixFieldTypes extends Migration
{
    public function up()
    {
        // Fix field types for PANEL1 fields that were incorrectly set
        $updates = [
            // Text Area fields should be textarea
            [
                'where' => ['field_label LIKE' => '%Text Area%', 'panel_name' => 'PANEL1'],
                'data' => ['field_type' => 'textarea']
            ],
            // Dropdown fields should be dropdown
            [
                'where' => ['field_label LIKE' => '%Dropdown%', 'panel_name' => 'PANEL1'],
                'data' => ['field_type' => 'dropdown']
            ]
        ];
        
        foreach ($updates as $update) {
            $this->db->table('dbpanel')
                ->where($update['where'])
                ->update($update['data']);
        }
    }

    public function down()
    {
        // Revert back to input if needed
        $this->db->table('dbpanel')
            ->where('panel_name', 'PANEL1')
            ->update(['field_type' => 'input']);
    }
}
