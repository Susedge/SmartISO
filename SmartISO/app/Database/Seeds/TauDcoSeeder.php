<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TauDcoSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'email'         => 'dco@tau.edu.ph',
            'username'      => 'tau_dco_user',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'full_name'     => 'TAU DCO Officer',
            'department_id' => null, // DCO is university-wide
            'office_id'     => null,
            'user_type'     => 'tau_dco',
            'active'        => 1,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        // Check if user already exists
        $existingUser = $this->db->table('users')
            ->where('username', $data['username'])
            ->get()
            ->getRow();

        if (!$existingUser) {
            $this->db->table('users')->insert($data);
            echo "TAU-DCO user created successfully!\n";
            echo "Username: tau_dco_user\n";
            echo "Password: password123\n";
        } else {
            echo "TAU-DCO user already exists.\n";
        }
    }
}
