<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SuperuserSeeder extends Seeder
{
    public function run()
    {
        // Get department ID for Computer Studies
        $departmentId = $this->db->table('departments')
            ->where('code', 'CS')
            ->get()
            ->getRow()
            ->id ?? 1;

        $data = [
            'email' => 'admin@smartiso.com',
            'username' => 'admin',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'full_name' => 'System Administrator',
            'department_id' => $departmentId,
            'user_type' => 'superuser',
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Using Query Builder to insert data
        $this->db->table('users')->insert($data);
    }
}
