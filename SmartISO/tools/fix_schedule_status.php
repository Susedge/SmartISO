<?php
/**
 * Fix Schedule Status Mismatch
 * Updates schedule status to match completed form submissions
 */

$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "=== FIXING SCHEDULE STATUS MISMATCHES ===\n\n";

// Find mismatches
$result = $mysqli->query("
    SELECT 
        s.id as schedule_id,
        s.submission_id,
        s.status as old_schedule_status,
        fs.status as submission_status
    FROM schedules s
    INNER JOIN form_submissions fs ON fs.id = s.submission_id
    WHERE fs.status = 'completed'
    AND s.status != 'completed'
");

$count = $result->num_rows;

if ($count > 0) {
    echo "Found {$count} schedule(s) to fix:\n\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "Fixing Schedule #{$row['schedule_id']} for Submission #{$row['submission_id']}\n";
        echo "  Old status: {$row['old_schedule_status']}\n";
        echo "  New status: completed\n";
        
        $updateResult = $mysqli->query("
            UPDATE schedules 
            SET status = 'completed' 
            WHERE id = {$row['schedule_id']}
        ");
        
        if ($updateResult) {
            echo "  ✓ Updated successfully\n\n";
        } else {
            echo "  ✗ Update failed: " . $mysqli->error . "\n\n";
        }
    }
    
    echo "=== FIX COMPLETED ===\n";
    echo "Total schedules fixed: {$count}\n\n";
    
} else {
    echo "✓ No mismatches found. All schedules are properly synced!\n\n";
}

$mysqli->close();

echo "Next steps:\n";
echo "1. On the other device: Clear browser cache (Ctrl+Shift+Delete)\n";
echo "2. Hard refresh the calendar page (Ctrl+F5)\n";
echo "3. Calendar should now show 'Completed' status correctly\n";
