<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTauDcoUserType extends Migration
{
    public function up()
    {
        // Check if tau_dco already exists in the ENUM
        $query = $this->db->query("SHOW COLUMNS FROM users WHERE Field='user_type'");
        $row = $query->getRow();
        
        if ($row) {
            $type = $row->Type;
            
            // Check if tau_dco is already in the ENUM
            if (strpos($type, 'tau_dco') === false) {
                // Add tau_dco to the user_type ENUM
                $sql = "ALTER TABLE `users` 
                        CHANGE `user_type` `user_type` 
                        ENUM('admin', 'requestor', 'approving_authority', 'service_staff', 'superuser', 'department_admin', 'tau_dco') 
                        DEFAULT 'requestor'";
                
                $this->db->query($sql);
                
                echo "✅ Added 'tau_dco' to user_type ENUM\n";
            } else {
                echo "ℹ️  'tau_dco' already exists in user_type ENUM\n";
            }
        }
    }

    public function down()
    {
        // Remove 'tau_dco' from the ENUM
        $sql = "ALTER TABLE `users` 
                CHANGE `user_type` `user_type` 
                ENUM('admin', 'requestor', 'approving_authority', 'service_staff', 'superuser', 'department_admin') 
                DEFAULT 'requestor'";
        
        $this->db->query($sql);
        
        echo "✅ Removed 'tau_dco' from user_type ENUM\n";
    }
}
