<?php
/**
 * Fix existing schedules to use approval date as scheduled date
 */

// Direct MySQLi connection
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "=== FIXING SCHEDULES TO USE APPROVAL DATE ===\n\n";

// Find schedules where scheduled_date doesn't match approval_date
$result = $mysqli->query("
    SELECT 
        s.id as schedule_id,
        s.submission_id,
        s.scheduled_date,
        s.priority_level,
        DATE(fs.approved_at) as approval_date,
        fs.approved_at,
        DATEDIFF(s.scheduled_date, DATE(fs.approved_at)) as date_diff
    FROM schedules s
    INNER JOIN form_submissions fs ON fs.id = s.submission_id
    WHERE fs.approved_at IS NOT NULL
      AND s.scheduled_date != DATE(fs.approved_at)
    ORDER BY fs.approved_at DESC
");

$issues = $result->fetch_all(MYSQLI_ASSOC);

if (empty($issues)) {
    echo "✓ No issues found - all schedules use approval date\n";
} else {
    echo "Found " . count($issues) . " schedules to fix:\n\n";
    
    foreach ($issues as $issue) {
        echo "  Schedule {$issue['schedule_id']} (Submission {$issue['submission_id']}):\n";
        echo "    Current scheduled_date: {$issue['scheduled_date']}\n";
        echo "    Should be (approval date): {$issue['approval_date']}\n";
        echo "    Priority: " . ($issue['priority_level'] ?? 'NULL') . "\n";
        echo "    Difference: {$issue['date_diff']} days\n\n";
    }
    
    $confirm = readline("Fix these schedules? (yes/no): ");
    
    if (strtolower(trim($confirm)) === 'yes') {
        echo "\nUpdating schedules...\n\n";
        
        $updated = 0;
        $errors = 0;
        
        foreach ($issues as $issue) {
            $scheduleId = $issue['schedule_id'];
            $approvalDate = $issue['approval_date'];
            $priorityLevel = $issue['priority_level'] ?? 'medium';
            
            // Calculate new ETA from approval date
            $etaDays = null;
            $estimatedDate = null;
            
            if ($priorityLevel === 'low') {
                $etaDays = 7;
                $estimatedDate = date('Y-m-d', strtotime($approvalDate . ' +7 days'));
            } elseif ($priorityLevel === 'medium') {
                $etaDays = 5;
                // Simple calculation - add 5 calendar days for now
                $estimatedDate = date('Y-m-d', strtotime($approvalDate . ' +5 days'));
            } elseif ($priorityLevel === 'high') {
                $etaDays = 3;
                $estimatedDate = date('Y-m-d', strtotime($approvalDate . ' +3 days'));
            }
            
            // Update the schedule
            $stmt = $mysqli->prepare("
                UPDATE schedules 
                SET scheduled_date = ?,
                    estimated_date = ?,
                    eta_days = ?
                WHERE id = ?
            ");
            
            $stmt->bind_param('ssii', $approvalDate, $estimatedDate, $etaDays, $scheduleId);
            
            if ($stmt->execute()) {
                echo "  ✓ Updated schedule {$scheduleId}: scheduled_date={$approvalDate}, eta_days={$etaDays}, estimated_date={$estimatedDate}\n";
                $updated++;
            } else {
                echo "  ✗ Failed to update schedule {$scheduleId}: " . $mysqli->error . "\n";
                $errors++;
            }
        }
        
        echo "\n✓ Updated {$updated} schedule(s)\n";
        if ($errors > 0) {
            echo "✗ Failed to update {$errors} schedule(s)\n";
        }
        
        // Verify
        echo "\nVerifying updates...\n";
        $result = $mysqli->query("
            SELECT COUNT(*) as count
            FROM schedules s
            INNER JOIN form_submissions fs ON fs.id = s.submission_id
            WHERE fs.approved_at IS NOT NULL
              AND s.scheduled_date != DATE(fs.approved_at)
        ");
        $row = $result->fetch_assoc();
        
        if ($row['count'] == 0) {
            echo "✓ All schedules now use approval date as scheduled date\n";
        } else {
            echo "✗ Still have {$row['count']} schedule(s) with mismatched dates\n";
        }
    } else {
        echo "Cancelled.\n";
    }
}

echo "\n=== DONE ===\n";
$mysqli->close();
