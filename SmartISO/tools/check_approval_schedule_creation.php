<?php
/**
 * Check if schedules are being created properly on approval
 */

// Direct MySQLi connection
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "=== CHECKING APPROVAL SCHEDULE CREATION ===\n\n";

// 1. Check form_submissions table structure
echo "1. Checking form_submissions table structure...\n";
$result = $mysqli->query("DESCRIBE form_submissions");
$columns = $result->fetch_all(MYSQLI_ASSOC);
echo "Columns in form_submissions:\n";
foreach ($columns as $col) {
    if (in_array($col['Field'], ['id', 'status', 'service_staff_id', 'approver_id', 'approved_at'])) {
        echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
    }
}
echo "\n";

// 2. Check schedules table structure
echo "2. Checking schedules table structure...\n";
$result = $mysqli->query("DESCRIBE schedules");
$columns = $result->fetch_all(MYSQLI_ASSOC);
echo "Columns in schedules:\n";
foreach ($columns as $col) {
    if (in_array($col['Field'], ['id', 'submission_id', 'assigned_staff_id', 'status', 'scheduled_date'])) {
        echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
    }
}
echo "\n";

// 3. Get recent approved submissions
echo "3. Recent approved submissions with service staff assigned:\n";
$result = $mysqli->query("
    SELECT 
        fs.id,
        fs.status,
        fs.service_staff_id,
        fs.approver_id,
        fs.approved_at,
        u.full_name as service_staff_name,
        f.code as form_code
    FROM form_submissions fs
    LEFT JOIN users u ON u.id = fs.service_staff_id
    LEFT JOIN forms f ON f.id = fs.form_id
    WHERE fs.status IN ('pending_service', 'approved')
      AND fs.service_staff_id IS NOT NULL
    ORDER BY fs.approved_at DESC
    LIMIT 10
");
$submissions = $result->fetch_all(MYSQLI_ASSOC);

if (empty($submissions)) {
    echo "  No approved submissions with service staff found.\n\n";
} else {
    foreach ($submissions as $sub) {
        echo "\n  Submission ID: {$sub['id']}\n";
        echo "    Form: {$sub['form_code']}\n";
        echo "    Status: {$sub['status']}\n";
        echo "    Service Staff: {$sub['service_staff_name']} (ID: {$sub['service_staff_id']})\n";
        echo "    Approved At: {$sub['approved_at']}\n";
        
        // Check if schedule exists
        $stmt = $mysqli->prepare("
            SELECT id, assigned_staff_id, status, scheduled_date, notes
            FROM schedules
            WHERE submission_id = ?
        ");
        $stmt->bind_param('i', $sub['id']);
        $stmt->execute();
        $schedResult = $stmt->get_result();
        $schedule = $schedResult->fetch_assoc();
        
        if ($schedule) {
            echo "    ✓ SCHEDULE EXISTS:\n";
            echo "      - Schedule ID: {$schedule['id']}\n";
            echo "      - Assigned Staff ID: {$schedule['assigned_staff_id']}\n";
            echo "      - Status: {$schedule['status']}\n";
            echo "      - Date: {$schedule['scheduled_date']}\n";
            echo "      - Notes: {$schedule['notes']}\n";
            
            // Check if assigned_staff_id matches
            if ($schedule['assigned_staff_id'] == $sub['service_staff_id']) {
                echo "      ✓ Staff ID MATCHES\n";
            } else {
                echo "      ✗ MISMATCH: Schedule staff ({$schedule['assigned_staff_id']}) != Submission staff ({$sub['service_staff_id']})\n";
            }
        } else {
            echo "    ✗ NO SCHEDULE FOUND - This is the problem!\n";
        }
    }
}

echo "\n\n4. Checking if there are submissions without schedules:\n";
$result = $mysqli->query("
    SELECT 
        fs.id,
        fs.status,
        fs.service_staff_id,
        u.full_name as service_staff_name,
        f.code as form_code
    FROM form_submissions fs
    LEFT JOIN users u ON u.id = fs.service_staff_id
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN schedules s ON s.submission_id = fs.id
    WHERE fs.status IN ('pending_service', 'approved')
      AND fs.service_staff_id IS NOT NULL
      AND s.id IS NULL
    ORDER BY fs.approved_at DESC
");
$missing = $result->fetch_all(MYSQLI_ASSOC);

if (empty($missing)) {
    echo "  ✓ All approved submissions have schedules\n";
} else {
    echo "  ✗ Found " . count($missing) . " submissions WITHOUT schedules:\n";
    foreach ($missing as $m) {
        echo "    - Submission {$m['id']} ({$m['form_code']}) - Status: {$m['status']}, Staff: {$m['service_staff_name']} (ID: {$m['service_staff_id']})\n";
    }
}

echo "\n\n=== ANALYSIS COMPLETE ===\n";
