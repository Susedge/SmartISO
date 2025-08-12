<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserTypesSeeder extends Seeder
{
    public function run()
    {
        // Get all departments for random assignment
        $departments = $this->db->table('departments')->get()->getResult();
        
        // Get all offices for assignment
        $offices = $this->db->table('offices')->get()->getResult();
        
        // Sample users for each type
        $users = [
            // Admin user
            [
                'email' => 'admin@example.com',
                'username' => 'admin_user',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'full_name' => 'Admin User',
                'department_id' => $departments[0]->id ?? 1,
                'office_id' => $offices[0]->id ?? 1, // Administration Office
                'user_type' => 'admin',
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            
            // Requestor user
            [
                'email' => 'requestor@example.com',
                'username' => 'requestor_user',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'full_name' => 'Requestor User',
                'department_id' => $departments[1]->id ?? 2,
                'office_id' => $offices[1]->id ?? 2, // IT Office
                'user_type' => 'requestor',
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            
            // Approving Authority user
            [
                'email' => 'approver@example.com',
                'username' => 'approver_user',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'full_name' => 'Approving Authority User',
                'department_id' => $departments[2]->id ?? 3,
                'office_id' => $offices[2]->id ?? 3, // HR Office
                'user_type' => 'approving_authority',
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            
            // Service Staff user
            [
                'email' => 'service@example.com',
                'username' => 'service_user',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'full_name' => 'Service Staff User',
                'department_id' => $departments[3]->id ?? 4,
                'office_id' => $offices[3]->id ?? 4, // Finance Office
                'user_type' => 'service_staff',
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        // Using Query Builder to insert data
        $this->db->table('users')->insertBatch($users);
    }
}
