<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTimezoneConfiguration extends Migration
{
    public function up()
    {
        // Add timezone configuration
        $data = [
            'config_key' => 'system_timezone',
            'config_value' => 'Asia/Singapore',
            'config_type' => 'string',
            'config_description' => 'System timezone setting (GMT+8)',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->table('configurations')->insert($data);
    }

    public function down()
    {
        $this->db->table('configurations')->where('config_key', 'system_timezone')->delete();
    }
}
