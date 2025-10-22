<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDepartmentAdminUserType extends Migration
{
    public function up()
    {
        // Modify the user_type ENUM to include 'department_admin'
        $sql = "ALTER TABLE `users` 
                CHANGE `user_type` `user_type` 
                ENUM('admin', 'requestor', 'approving_authority', 'service_staff', 'superuser', 'department_admin') 
                DEFAULT 'requestor'";
        
        $this->db->query($sql);
    }

    public function down()
    {
        // Remove 'department_admin' from the ENUM
        $sql = "ALTER TABLE `users` 
                CHANGE `user_type` `user_type` 
                ENUM('admin', 'requestor', 'approving_authority', 'service_staff', 'superuser') 
                DEFAULT 'requestor'";
        
        $this->db->query($sql);
    }
}
