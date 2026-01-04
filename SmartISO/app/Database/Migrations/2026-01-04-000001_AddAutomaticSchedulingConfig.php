<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAutomaticSchedulingConfig extends Migration
{
    public function up()
    {
        $data = [
            [
                'config_key' => 'auto_create_schedule_on_submit',
                'config_value' => '0',
                'config_description' => 'Automatically create schedule entry when a submission is created',
                'config_type' => 'boolean',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'config_key' => 'auto_create_schedule_on_approval',
                'config_value' => '1',
                'config_description' => 'Automatically create schedule entry when a submission is approved',
                'config_type' => 'boolean',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
        
        // Use insertBatch with ignore to prevent duplicates
        foreach ($data as $row) {
            $exists = $this->db->table('configurations')
                ->where('config_key', $row['config_key'])
                ->countAllResults();
            
            if ($exists === 0) {
                $this->db->table('configurations')->insert($row);
            }
        }
    }

    public function down()
    {
        $this->db->table('configurations')
            ->whereIn('config_key', [
                'auto_create_schedule_on_submit',
                'auto_create_schedule_on_approval'
            ])
            ->delete();
    }
}
