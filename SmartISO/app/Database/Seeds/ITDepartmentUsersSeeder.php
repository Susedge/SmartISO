<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ITDepartmentUsersSeeder extends Seeder
{
    public function run()
    {
        // Add some test users for IT department
        $users = [
            [
                'username' => 'it_requestor_1',
                'email' => 'it_requestor1@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'full_name' => 'IT Requestor One',
                'user_type' => 'requestor',
                'department_id' => 22, // IT department
                'office_id' => 2,
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'username' => 'it_requestor_2',
                'email' => 'it_requestor2@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'full_name' => 'IT Requestor Two',
                'user_type' => 'requestor',
                'department_id' => 22, // IT department
                'office_id' => 2,
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'username' => 'it_approver',
                'email' => 'it_approver@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'full_name' => 'IT Approving Authority',
                'user_type' => 'approving_authority',
                'department_id' => 22, // IT department
                'office_id' => 2,
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'username' => 'it_service',
                'email' => 'it_service@example.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'full_name' => 'IT Service Staff',
                'user_type' => 'service_staff',
                'department_id' => 22, // IT department
                'office_id' => 2,
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        foreach ($users as $user) {
            // Check if user already exists
            $existing = $this->db->table('users')->where('username', $user['username'])->get()->getRow();
            
            if (!$existing) {
                $this->db->table('users')->insert($user);
                echo "Created user: {$user['username']}\n";
            } else {
                echo "User already exists: {$user['username']}\n";
            }
        }
    }
}
