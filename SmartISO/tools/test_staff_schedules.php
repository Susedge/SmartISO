<?php

/**
 * Test script for getStaffSchedules method
 * This script tests the ScheduleModel::getStaffSchedules() method and logs the query and results.
 * 
 * Usage: php test_staff_schedules.php [staff_id] [date]
 * Example: php test_staff_schedules.php 5
 * Example: php test_staff_schedules.php 5 2025-11-20
 */

// Load CodeIgniter
require_once dirname(__DIR__) . '/preload.php';
require_once dirname(__DIR__) . '/app/Config/Paths.php';

$paths = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$app = require realpath($bootstrap) ?: $bootstrap;

// Get command line arguments
$staffId = $argv[1] ?? null;
$date = $argv[2] ?? null;

if (!$staffId) {
    echo "Error: Please provide a staff ID\n";
    echo "Usage: php test_staff_schedules.php [staff_id] [date]\n";
    echo "Example: php test_staff_schedules.php 5\n";
    echo "Example: php test_staff_schedules.php 5 2025-11-20\n";
    exit(1);
}

echo "=" . str_repeat("=", 78) . "\n";
echo "Testing getStaffSchedules Method\n";
echo "=" . str_repeat("=", 78) . "\n";
echo "Staff ID: $staffId\n";
echo "Date: " . ($date ?? 'null (all dates)') . "\n";
echo str_repeat("-", 80) . "\n\n";

try {
    // Initialize the model
    $scheduleModel = new \App\Models\ScheduleModel();
    
    // Get staff details
    $userModel = new \App\Models\UserModel();
    $staff = $userModel->find($staffId);
    
    if (!$staff) {
        echo "Error: Staff member with ID $staffId not found\n";
        exit(1);
    }
    
    echo "Staff Details:\n";
    echo "  Name: " . $staff['full_name'] . "\n";
    echo "  Email: " . $staff['email'] . "\n";
    echo "  Type: " . $staff['user_type'] . "\n";
    echo "  Department ID: " . ($staff['department_id'] ?? 'N/A') . "\n\n";
    
    // Call the method
    echo "Calling getStaffSchedules($staffId, " . ($date ?? 'null') . ")...\n\n";
    $schedules = $scheduleModel->getStaffSchedules($staffId, $date);
    
    // Display results
    echo "Results:\n";
    echo "  Count: " . count($schedules) . "\n\n";
    
    if (empty($schedules)) {
        echo "No schedules found for this staff member.\n";
    } else {
        echo "Schedule Details:\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($schedules as $index => $schedule) {
            $num = $index + 1;
            echo "\n[$num] Schedule ID: {$schedule['id']}\n";
            echo "    Submission ID: {$schedule['submission_id']}\n";
            echo "    Form Code: " . ($schedule['form_code'] ?? 'N/A') . "\n";
            echo "    Form Description: " . ($schedule['form_description'] ?? 'N/A') . "\n";
            echo "    Panel Name: " . ($schedule['panel_name'] ?? 'N/A') . "\n";
            echo "    Requestor: " . ($schedule['requestor_name'] ?? 'N/A') . "\n";
            echo "    Scheduled Date: {$schedule['scheduled_date']}\n";
            echo "    Scheduled Time: {$schedule['scheduled_time']}\n";
            echo "    Duration: {$schedule['duration_minutes']} minutes\n";
            echo "    Status: {$schedule['status']}\n";
            echo "    Submission Status: " . ($schedule['submission_status'] ?? 'N/A') . "\n";
            echo "    Location: " . ($schedule['location'] ?? 'N/A') . "\n";
            echo "    Priority: " . ($schedule['priority'] ?? 0) . "\n";
            echo "    Priority Level: " . ($schedule['priority_level'] ?? 'N/A') . "\n";
            echo "    ETA Days: " . ($schedule['eta_days'] ?? 'N/A') . "\n";
            echo "    Estimated Date: " . ($schedule['estimated_date'] ?? 'N/A') . "\n";
            echo "    Manual Schedule: " . ($schedule['is_manual_schedule'] ?? 0) . "\n";
            echo "    Notes: " . ($schedule['notes'] ?? 'N/A') . "\n";
            echo str_repeat("-", 80) . "\n";
        }
    }
    
    // Check for direct service staff assignments (submissions without schedules)
    echo "\n\nChecking for submissions assigned to this staff without schedules...\n";
    $db = \Config\Database::connect();
    $builder = $db->table('form_submissions fs');
    $builder->select('fs.id, fs.form_id, fs.panel_name, fs.status, fs.created_at,
                      f.code as form_code, f.description as form_description,
                      u.full_name as requestor_name')
        ->join('forms f', 'f.id = fs.form_id', 'left')
        ->join('users u', 'u.id = fs.submitted_by', 'left')
        ->where('fs.service_staff_id', $staffId)
        ->where('NOT EXISTS (SELECT 1 FROM schedules s WHERE s.submission_id = fs.id)', null, false)
        ->whereIn('fs.status', ['approved', 'pending_service', 'submitted']);
    
    $unscheduled = $builder->get()->getResultArray();
    
    echo "Unscheduled Submissions Count: " . count($unscheduled) . "\n\n";
    
    if (!empty($unscheduled)) {
        echo "Unscheduled Submission Details:\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($unscheduled as $index => $sub) {
            $num = $index + 1;
            echo "\n[$num] Submission ID: {$sub['id']}\n";
            echo "    Form Code: " . ($sub['form_code'] ?? 'N/A') . "\n";
            echo "    Form Description: " . ($sub['form_description'] ?? 'N/A') . "\n";
            echo "    Panel Name: " . ($sub['panel_name'] ?? 'N/A') . "\n";
            echo "    Requestor: " . ($sub['requestor_name'] ?? 'N/A') . "\n";
            echo "    Status: {$sub['status']}\n";
            echo "    Created At: {$sub['created_at']}\n";
            echo str_repeat("-", 80) . "\n";
        }
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "Test completed. Check the logs at writable/logs/ for detailed query information.\n";
    echo str_repeat("=", 80) . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
