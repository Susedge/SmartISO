<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAdminCanApproveConfig extends Migration
{
    public function up()
    {
        // Check if the configuration already exists
        $exists = $this->db->table('configurations')
            ->where('config_key', 'admin_can_approve')
            ->countAllResults() > 0;
        
        if (!$exists) {
            $data = [
                'config_key' => 'admin_can_approve',
                'config_value' => '1',
                'config_description' => 'Allow global admins to act as approvers (1 = enabled, 0 = disabled)',
                'config_type' => 'boolean',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            $this->db->table('configurations')->insert($data);
        }
    }

    public function down()
    {
        $this->db->table('configurations')
            ->where('config_key', 'admin_can_approve')
            ->delete();
    }
}
