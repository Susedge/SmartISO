<?php
/**
 * Diagnostic Script: getStaffSchedules Query and Results
 * 
 * This script shows the exact SQL query used by getStaffSchedules()
 * and displays the complete result set.
 * 
 * Usage: php tools/check_getStaffSchedules.php [staff_id]
 */

// Direct PDO connection
$pdo = new PDO('mysql:host=localhost;dbname=smartiso', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get staff ID from command line argument or default to 5
$staffId = isset($argv[1]) ? (int)$argv[1] : 5;

echo "=============================================================\n";
echo "DIAGNOSTIC: getStaffSchedules() Query and Results\n";
echo "=============================================================\n";
echo "Staff ID: " . $staffId . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "=============================================================\n\n";

// Build the exact same query as ScheduleModel::getStaffSchedules()
$sql = "SELECT s.*, 
        fs.form_id, fs.panel_name, fs.status as submission_status,
        f.code as form_code, f.description as form_description,
        u.full_name as requestor_name, 
        staff.full_name as assigned_staff_name
FROM schedules s
LEFT JOIN form_submissions fs ON fs.id = s.submission_id
LEFT JOIN forms f ON f.id = fs.form_id
LEFT JOIN users u ON u.id = fs.submitted_by
LEFT JOIN users staff ON staff.id = s.assigned_staff_id
WHERE s.assigned_staff_id = :staffId
ORDER BY s.scheduled_date ASC, s.scheduled_time ASC";

echo "SQL QUERY:\n";
echo "-------------------------------------------------------------\n";
echo str_replace(':staffId', $staffId, $sql) . "\n";
echo "-------------------------------------------------------------\n\n";

// Execute the query
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':staffId', $staffId, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "RESULT COUNT: " . count($results) . "\n";
echo "=============================================================\n\n";

if (empty($results)) {
    echo "❌ NO SCHEDULES FOUND for staff_id = {$staffId}\n\n";
    
    // Check if staff exists
    $staffStmt = $pdo->prepare("SELECT id, full_name, user_type FROM users WHERE id = :staffId");
    $staffStmt->bindValue(':staffId', $staffId, PDO::PARAM_INT);
    $staffStmt->execute();
    $staffCheck = $staffStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($staffCheck) {
        echo "✅ Staff user exists:\n";
        echo "   - ID: {$staffCheck['id']}\n";
        echo "   - Name: {$staffCheck['full_name']}\n";
        echo "   - User Type: {$staffCheck['user_type']}\n\n";
    } else {
        echo "❌ Staff user does NOT exist in users table\n\n";
    }
    
    // Check if there are any schedules with this assigned_staff_id
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM schedules WHERE assigned_staff_id = :staffId");
    $countStmt->bindValue(':staffId', $staffId, PDO::PARAM_INT);
    $countStmt->execute();
    $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
    echo "Total schedules with assigned_staff_id = {$staffId}: {$countRow['total']}\n\n";
    
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

$rawStmt = $pdo->prepare("SELECT COUNT(*) as total FROM schedules WHERE assigned_staff_id = :staffId");
$rawStmt->bindValue(':staffId', $staffId, PDO::PARAM_INT);
$rawStmt->execute();
$rawResult = $rawStmt->fetch(PDO::FETCH_ASSOC);
echo "Raw count from schedules table: {$rawResult['total']}\n";

$rawStmt2 = $pdo->prepare("SELECT id, submission_id, assigned_staff_id, status, scheduled_date, scheduled_time 
              FROM schedules 
              WHERE assigned_staff_id = :staffId 
              ORDER BY scheduled_date ASC, scheduled_time ASC");
$rawStmt2->bindValue(':staffId', $staffId, PDO::PARAM_INT);
$rawStmt2->execute();
$rawResults = $rawStmt2->fetchAll(PDO::FETCH_ASSOC);

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
