<?php
/**
 * Check recently approved submissions and their schedule entries
 */

// Direct MySQLi connection
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "=== CHECKING RECENT APPROVALS AND SCHEDULES ===\n\n";

// Find submissions approved in the last 24 hours
$result = $mysqli->query("
    SELECT 
        fs.id as submission_id,
        fs.status,
        fs.approved_at,
        DATE(fs.approved_at) as approval_date,
        fs.service_staff_id,
        fs.approver_id,
        u.full_name as staff_name,
        s.id as schedule_id,
        s.scheduled_date,
        s.assigned_staff_id,
        s.status as schedule_status,
        DATEDIFF(s.scheduled_date, DATE(fs.approved_at)) as date_diff
    FROM form_submissions fs
    LEFT JOIN schedules s ON s.submission_id = fs.id
    LEFT JOIN users u ON u.id = fs.service_staff_id
    WHERE fs.approved_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY fs.approved_at DESC
");

if (!$result) {
    die("Query failed: " . $mysqli->error);
}

$approvals = $result->fetch_all(MYSQLI_ASSOC);

if (empty($approvals)) {
    echo "No approvals in the last 24 hours.\n";
} else {
    echo "Found " . count($approvals) . " approval(s) in the last 24 hours:\n\n";
    
    foreach ($approvals as $approval) {
        echo "Submission ID: {$approval['submission_id']}\n";
        echo "  Status: {$approval['status']}\n";
        echo "  Approved at: {$approval['approved_at']}\n";
        echo "  Approval date: {$approval['approval_date']}\n";
        echo "  Service staff ID: " . ($approval['service_staff_id'] ?? 'NULL') . "\n";
        echo "  Service staff name: " . ($approval['staff_name'] ?? 'NULL') . "\n";
        echo "  Approver ID: " . ($approval['approver_id'] ?? 'NULL') . "\n";
        
        if ($approval['schedule_id']) {
            echo "  ✓ Schedule exists (ID: {$approval['schedule_id']})\n";
            echo "    Scheduled date: {$approval['scheduled_date']}\n";
            echo "    Assigned staff: " . ($approval['assigned_staff_id'] ?? 'NULL') . "\n";
            echo "    Schedule status: {$approval['schedule_status']}\n";
            
            if ($approval['date_diff'] != 0) {
                echo "    ⚠ WARNING: Scheduled date differs from approval date by {$approval['date_diff']} days\n";
            } else {
                echo "    ✓ Scheduled date matches approval date\n";
            }
        } else {
            echo "  ✗ NO SCHEDULE ENTRY FOUND\n";
            echo "    This submission will not appear on the calendar!\n";
        }
        echo "\n";
    }
}

echo "\n=== CHECKING ALL APPROVED SUBMISSIONS WITHOUT SCHEDULES ===\n\n";

$result = $mysqli->query("
    SELECT 
        fs.id as submission_id,
        fs.status,
        fs.approved_at,
        DATE(fs.approved_at) as approval_date,
        fs.service_staff_id,
        u.full_name as staff_name
    FROM form_submissions fs
    LEFT JOIN schedules s ON s.submission_id = fs.id
    LEFT JOIN users u ON u.id = fs.service_staff_id
    WHERE fs.approved_at IS NOT NULL
      AND s.id IS NULL
    ORDER BY fs.approved_at DESC
    LIMIT 20
");

$missing = $result->fetch_all(MYSQLI_ASSOC);

if (empty($missing)) {
    echo "✓ All approved submissions have schedule entries\n";
} else {
    echo "Found " . count($missing) . " approved submissions WITHOUT schedules:\n\n";
    
    foreach ($missing as $item) {
        echo "  Submission ID: {$item['submission_id']}\n";
        echo "    Status: {$item['status']}\n";
        echo "    Approved at: {$item['approved_at']}\n";
        echo "    Service staff: " . ($item['staff_name'] ?? 'NULL') . "\n\n";
    }
}

echo "\n=== DONE ===\n";
$mysqli->close();
