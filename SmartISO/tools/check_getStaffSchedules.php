<?php
/**
 * Diagnostic Script: getStaffSchedules Query and Results
 * 
 * This script shows the exact SQL query used by getStaffSchedules()
 * and displays the complete result set.
 * 
 * Usage: php tools/check_getStaffSchedules.php [staff_id]
 */

// Load CodeIgniter
require_once dirname(__DIR__) . '/app/Config/Paths.php';
$paths = new Config\Paths();
require_once $paths->systemDirectory . '/bootstrap.php';

$app = Config\Services::codeigniter();
$app->initialize();

// Get database connection
$db = \Config\Database::connect();

// Get staff ID from command line argument or default to 5
$staffId = isset($argv[1]) ? (int)$argv[1] : 5;

echo "=============================================================\n";
echo "DIAGNOSTIC: getStaffSchedules() Query and Results\n";
echo "=============================================================\n";
echo "Staff ID: " . $staffId . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "=============================================================\n\n";

// Build the exact same query as ScheduleModel::getStaffSchedules()
$builder = $db->table('schedules s');
$builder->select('s.*, fs.form_id, fs.panel_name, fs.status as submission_status,
                  f.code as form_code, f.description as form_description,
                  u.full_name as requestor_name, staff.full_name as assigned_staff_name')
    ->join('form_submissions fs', 'fs.id = s.submission_id', 'left')
    ->join('forms f', 'f.id = fs.form_id', 'left')
    ->join('users u', 'u.id = fs.submitted_by', 'left')
    ->join('users staff', 'staff.id = s.assigned_staff_id', 'left')
    ->where('s.assigned_staff_id', $staffId)
    ->orderBy('s.scheduled_date', 'ASC')
    ->orderBy('s.scheduled_time', 'ASC');

// Get the compiled SQL query
$sql = $builder->getCompiledSelect(false);

echo "SQL QUERY:\n";
echo "-------------------------------------------------------------\n";
echo $sql . "\n";
echo "-------------------------------------------------------------\n\n";

// Execute the query
$results = $builder->get()->getResultArray();

echo "RESULT COUNT: " . count($results) . "\n";
echo "=============================================================\n\n";

if (empty($results)) {
    echo "❌ NO SCHEDULES FOUND for staff_id = {$staffId}\n\n";
    
    // Check if staff exists
    $staffCheck = $db->table('users')->where('id', $staffId)->get()->getRowArray();
    if ($staffCheck) {
        echo "✅ Staff user exists:\n";
        echo "   - ID: {$staffCheck['id']}\n";
        echo "   - Name: {$staffCheck['full_name']}\n";
        echo "   - User Type: {$staffCheck['user_type']}\n\n";
    } else {
        echo "❌ Staff user does NOT exist in users table\n\n";
    }
    
    // Check if there are any schedules with this assigned_staff_id
    $scheduleCount = $db->table('schedules')
        ->where('assigned_staff_id', $staffId)
        ->countAllResults();
    echo "Total schedules with assigned_staff_id = {$staffId}: {$scheduleCount}\n\n";
    
} else {
    echo "RESULT SET:\n";
    echo "=============================================================\n";
    
    $counter = 1;
    foreach ($results as $row) {
        echo "\n[{$counter}] Schedule ID: {$row['id']}\n";
        echo "-------------------------------------------------------------\n";
        echo "Submission ID:      {$row['submission_id']}\n";
        echo "Form:               {$row['form_description']} ({$row['form_code']})\n";
        echo "Panel Name:         {$row['panel_name']}\n";
        echo "Requestor:          {$row['requestor_name']}\n";
        echo "Assigned Staff:     {$row['assigned_staff_name']} (ID: {$row['assigned_staff_id']})\n";
        echo "Scheduled Date:     {$row['scheduled_date']}\n";
        echo "Scheduled Time:     {$row['scheduled_time']}\n";
        echo "Schedule Status:    {$row['status']}\n";
        echo "Submission Status:  {$row['submission_status']}\n";
        echo "Priority:           {$row['priority']}\n";
        echo "Priority Level:     " . ($row['priority_level'] ?? 'NULL') . "\n";
        echo "Notes:              {$row['notes']}\n";
        echo "Estimated Date:     " . ($row['estimated_date'] ?? 'NULL') . "\n";
        echo "ETA Days:           " . ($row['eta_days'] ?? 'NULL') . "\n";
        echo "Created At:         {$row['created_at']}\n";
        echo "Updated At:         {$row['updated_at']}\n";
        
        $counter++;
    }
    
    echo "\n=============================================================\n";
    echo "SUMMARY:\n";
    echo "-------------------------------------------------------------\n";
    echo "Total Schedules:    " . count($results) . "\n";
    
    // Count by status
    $statusCounts = [];
    $submissionStatusCounts = [];
    foreach ($results as $row) {
        $status = $row['status'];
        $subStatus = $row['submission_status'];
        
        if (!isset($statusCounts[$status])) {
            $statusCounts[$status] = 0;
        }
        $statusCounts[$status]++;
        
        if (!isset($submissionStatusCounts[$subStatus])) {
            $submissionStatusCounts[$subStatus] = 0;
        }
        $submissionStatusCounts[$subStatus]++;
    }
    
    echo "\nBy Schedule Status:\n";
    foreach ($statusCounts as $status => $count) {
        echo "  - {$status}: {$count}\n";
    }
    
    echo "\nBy Submission Status:\n";
    foreach ($submissionStatusCounts as $status => $count) {
        echo "  - {$status}: {$count}\n";
    }
    
    echo "=============================================================\n";
}

// Also check the database directly with raw query
echo "\n\n";
echo "=============================================================\n";
echo "DIRECT DATABASE CHECK (RAW QUERY)\n";
echo "=============================================================\n";

$rawQuery = "SELECT COUNT(*) as total FROM schedules WHERE assigned_staff_id = {$staffId}";
$rawResult = $db->query($rawQuery)->getRowArray();
echo "Raw count from schedules table: {$rawResult['total']}\n";

$rawQuery2 = "SELECT id, submission_id, assigned_staff_id, status, scheduled_date, scheduled_time 
              FROM schedules 
              WHERE assigned_staff_id = {$staffId} 
              ORDER BY scheduled_date ASC, scheduled_time ASC";
$rawResults = $db->query($rawQuery2)->getResultArray();

if (!empty($rawResults)) {
    echo "\nRaw schedule records:\n";
    foreach ($rawResults as $idx => $row) {
        echo sprintf(
            "  [%d] ID: %d | Submission: %d | Staff: %d | Status: %s | Date: %s %s\n",
            $idx + 1,
            $row['id'],
            $row['submission_id'],
            $row['assigned_staff_id'],
            $row['status'],
            $row['scheduled_date'],
            $row['scheduled_time']
        );
    }
}

echo "=============================================================\n";
echo "\nDiagnostic complete!\n";
