<?php
/**
 * Fix schedules with NULL assigned_staff_id by syncing from form_submissions.service_staff_id
 */

// Direct MySQLi connection
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "=== FIXING SCHEDULES WITH MISSING assigned_staff_id ===\n\n";

// Find schedules where assigned_staff_id is NULL but submission has service_staff_id
$result = $mysqli->query("
    SELECT 
        s.id as schedule_id,
        s.submission_id,
        s.assigned_staff_id as current_assigned,
        fs.service_staff_id as should_be_assigned,
        u.full_name as staff_name
    FROM schedules s
    INNER JOIN form_submissions fs ON fs.id = s.submission_id
    LEFT JOIN users u ON u.id = fs.service_staff_id
    WHERE fs.service_staff_id IS NOT NULL
      AND (s.assigned_staff_id IS NULL OR s.assigned_staff_id != fs.service_staff_id)
");

$issues = $result->fetch_all(MYSQLI_ASSOC);

if (empty($issues)) {
    echo "✓ No issues found - all schedules have correct assigned_staff_id\n";
} else {
    echo "Found " . count($issues) . " schedules needing repair:\n\n";
    
    foreach ($issues as $issue) {
        echo "  Schedule ID: {$issue['schedule_id']}\n";
        echo "    Submission: {$issue['submission_id']}\n";
        echo "    Current assigned_staff_id: " . ($issue['current_assigned'] ?? 'NULL') . "\n";
        echo "    Should be: {$issue['should_be_assigned']} ({$issue['staff_name']})\n\n";
    }
    
    $confirm = readline("Fix these issues? (yes/no): ");
    
    if (strtolower(trim($confirm)) === 'yes') {
        echo "\nUpdating schedules...\n";
        
        $stmt = $mysqli->prepare("
            UPDATE schedules s
            INNER JOIN form_submissions fs ON fs.id = s.submission_id
            SET s.assigned_staff_id = fs.service_staff_id
            WHERE fs.service_staff_id IS NOT NULL
              AND (s.assigned_staff_id IS NULL OR s.assigned_staff_id != fs.service_staff_id)
        ");
        
        if ($stmt->execute()) {
            $affected = $mysqli->affected_rows;
            echo "✓ Successfully updated {$affected} schedule(s)\n";
            
            // Verify
            echo "\nVerifying updates...\n";
            foreach ($issues as $issue) {
                $verifyStmt = $mysqli->prepare("
                    SELECT assigned_staff_id 
                    FROM schedules 
                    WHERE id = ?
                ");
                $verifyStmt->bind_param('i', $issue['schedule_id']);
                $verifyStmt->execute();
                $verifyResult = $verifyStmt->get_result();
                $row = $verifyResult->fetch_assoc();
                
                if ($row['assigned_staff_id'] == $issue['should_be_assigned']) {
                    echo "  ✓ Schedule {$issue['schedule_id']} now has assigned_staff_id = {$row['assigned_staff_id']}\n";
                } else {
                    echo "  ✗ Schedule {$issue['schedule_id']} still has issue\n";
                }
            }
        } else {
            echo "✗ Update failed: " . $mysqli->error . "\n";
        }
    } else {
        echo "Cancelled.\n";
    }
}

echo "\n=== DONE ===\n";
$mysqli->close();
