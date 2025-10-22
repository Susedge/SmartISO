<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DepartmentAdminSeeder extends Seeder
{
    public function run()
    {
        // Get departments - we'll assign the department admin to IT (Computer Studies department)
        $departments = $this->db->table('departments')->get()->getResult();
        
        // Find the IT/Computer Studies department
        $itDepartment = null;
        foreach ($departments as $dept) {
            if (in_array($dept->code, ['CS', 'IT'])) {
                $itDepartment = $dept;
                break;
            }
        }
        
        // Fallback to second department if CS/IT not found
        if (!$itDepartment && count($departments) > 1) {
            $itDepartment = $departments[1];
        }
        
        // Get offices - we'll use IT Office if available
        $offices = $this->db->table('offices')->get()->getResult();
        $itOffice = null;
        foreach ($offices as $office) {
            if (stripos($office->description, 'IT') !== false || stripos($office->code, 'IT') !== false) {
                $itOffice = $office;
                break;
            }
        }
        
        // Fallback to second office if IT Office not found
        if (!$itOffice && count($offices) > 1) {
            $itOffice = $offices[1];
        }
        
        // Create department admin user
        $departmentAdminUser = [
            'email' => 'dept_admin@example.com',
            'username' => 'dept_admin_it',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'full_name' => 'IT Department Admin',
            'department_id' => $itDepartment ? $itDepartment->id : 2,
            'office_id' => $itOffice ? $itOffice->id : 2,
            'user_type' => 'department_admin',
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        // Check if user already exists
        $existingUser = $this->db->table('users')
            ->where('email', $departmentAdminUser['email'])
            ->orWhere('username', $departmentAdminUser['username'])
            ->get()
            ->getRow();
        
        if (!$existingUser) {
            $this->db->table('users')->insert($departmentAdminUser);
            echo "Department Admin user created successfully.\n";
        } else {
            echo "Department Admin user already exists.\n";
        }
    }
}
