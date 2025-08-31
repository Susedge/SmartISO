<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FormOfficeSeeder extends Seeder
{
    public function run()
    {
        // Get all forms
        $forms = $this->db->table('forms')->get()->getResultArray();
        
        // Get all active offices
        $offices = $this->db->table('offices')->where('active', 1)->get()->getResultArray();
        
        if (empty($forms) || empty($offices)) {
            echo "No forms or offices found. Skipping form-office assignment.\n";
            return;
        }
        
        // Create a mapping of forms to offices
        // You can customize this logic based on your requirements
        $officeAssignments = [];
        
        foreach ($forms as $index => $form) {
            // Assign forms to offices in a round-robin fashion
            $officeIndex = $index % count($offices);
            $officeAssignments[] = [
                'id' => $form['id'],
                'office_id' => $offices[$officeIndex]['id'],
                'office_name' => $offices[$officeIndex]['description']
            ];
        }
        
        // Update forms with office assignments
        foreach ($officeAssignments as $assignment) {
            $this->db->table('forms')
                     ->where('id', $assignment['id'])
                     ->update(['office_id' => $assignment['office_id']]);
            
            echo "Assigned form ID {$assignment['id']} to office: {$assignment['office_name']}\n";
        }
        
        echo "Form-office assignment completed. Total assignments: " . count($officeAssignments) . "\n";
    }
}
