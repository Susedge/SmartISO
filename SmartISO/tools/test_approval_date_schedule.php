<?php
/**
 * Test approval date as scheduled date
 */

// Direct MySQLi connection
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "=== TESTING APPROVAL DATE AS SCHEDULED DATE ===\n\n";

// Get recent approved submissions
echo "1. Checking recent approved submissions:\n\n";
$result = $mysqli->query("
    SELECT 
        fs.id,
        fs.status,
        fs.approved_at,
        DATE(fs.approved_at) as approval_date,
        s.id as schedule_id,
        s.scheduled_date,
        s.estimated_date,
        s.eta_days,
        s.priority_level,
        f.code as form_code
    FROM form_submissions fs
    LEFT JOIN schedules s ON s.submission_id = fs.id
    LEFT JOIN forms f ON f.id = fs.form_id
    WHERE fs.status IN ('pending_service', 'approved', 'completed')
      AND fs.approved_at IS NOT NULL
    ORDER BY fs.approved_at DESC
    LIMIT 10
");

$submissions = $result->fetch_all(MYSQLI_ASSOC);

foreach ($submissions as $sub) {
    echo "Submission {$sub['id']} ({$sub['form_code']}):\n";
    echo "  Status: {$sub['status']}\n";
    echo "  Approved At: {$sub['approved_at']}\n";
    echo "  Approval Date: {$sub['approval_date']}\n";
    
    if ($sub['schedule_id']) {
        echo "  Schedule ID: {$sub['schedule_id']}\n";
        echo "  Scheduled Date: {$sub['scheduled_date']}\n";
        echo "  Priority: {$sub['priority_level']}\n";
        echo "  ETA Days: " . ($sub['eta_days'] ?? 'NULL') . "\n";
        echo "  Estimated Date: " . ($sub['estimated_date'] ?? 'NULL') . "\n";
        
        // Check if scheduled_date matches approval_date
        if ($sub['scheduled_date'] == $sub['approval_date']) {
            echo "  ✓ Scheduled date MATCHES approval date\n";
        } else {
            echo "  ✗ Scheduled date ({$sub['scheduled_date']}) does NOT match approval date ({$sub['approval_date']})\n";
        }
        
        // Check if estimated_date is >= scheduled_date
        if ($sub['estimated_date']) {
            if ($sub['estimated_date'] >= $sub['scheduled_date']) {
                echo "  ✓ ETA ({$sub['estimated_date']}) is on or after scheduled date ({$sub['scheduled_date']})\n";
            } else {
                echo "  ✗ PROBLEM: ETA ({$sub['estimated_date']}) is BEFORE scheduled date ({$sub['scheduled_date']})\n";
            }
        }
    } else {
        echo "  No schedule found\n";
    }
    echo "\n";
}

echo "\n2. Summary Check:\n";
$result = $mysqli->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN s.scheduled_date = DATE(fs.approved_at) THEN 1 ELSE 0 END) as matching,
        SUM(CASE WHEN s.estimated_date < s.scheduled_date THEN 1 ELSE 0 END) as eta_before_scheduled
    FROM form_submissions fs
    INNER JOIN schedules s ON s.submission_id = fs.id
    WHERE fs.status IN ('pending_service', 'approved', 'completed')
      AND fs.approved_at IS NOT NULL
");

$summary = $result->fetch_assoc();
echo "  Total approved with schedules: {$summary['total']}\n";
echo "  Scheduled date matches approval date: {$summary['matching']} / {$summary['total']}\n";
echo "  ETA before scheduled date (ERROR): {$summary['eta_before_scheduled']}\n";

if ($summary['matching'] == $summary['total']) {
    echo "\n  ✓ All schedules use approval date as scheduled date\n";
} else {
    $needFix = $summary['total'] - $summary['matching'];
    echo "\n  ✗ {$needFix} schedule(s) need to be updated\n";
}

if ($summary['eta_before_scheduled'] == 0) {
    echo "  ✓ All ETAs are valid (on or after scheduled date)\n";
} else {
    echo "  ✗ {$summary['eta_before_scheduled']} schedule(s) have invalid ETA (before scheduled date)\n";
}

echo "\n=== TEST COMPLETE ===\n";
$mysqli->close();
