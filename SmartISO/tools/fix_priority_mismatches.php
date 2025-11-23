<?php
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

echo "=== FIXING PRIORITY MISMATCHES ===\n\n";

// Find all schedules where priority_level is not 'low' but should be
$result = $mysqli->query("
    SELECT s.id, s.submission_id, s.priority_level, fs.priority as submission_priority
    FROM schedules s 
    JOIN form_submissions fs ON s.submission_id = fs.id 
    WHERE fs.status IN ('approved', 'pending_service')
    AND (s.priority_level != 'low' OR fs.priority != 'low')
");

$toFix = [];
while ($row = $result->fetch_assoc()) {
    $toFix[] = $row;
}

echo "Found " . count($toFix) . " schedules/submissions with non-low priority\n\n";

foreach ($toFix as $item) {
    echo "Processing Schedule ID {$item['id']} (Submission {$item['submission_id']}):\n";
    echo "  Current: submission.priority={$item['submission_priority']}, schedule.priority_level={$item['priority_level']}\n";
    
    // Fix submission priority to 'low'
    if ($item['submission_priority'] !== 'low') {
        $mysqli->query("UPDATE form_submissions SET priority='low' WHERE id={$item['submission_id']}");
        echo "  ✓ Updated submission priority to 'low'\n";
    }
    
    // Fix schedule priority_level to 'low' and recalculate ETA
    if ($item['priority_level'] !== 'low') {
        // Get the scheduled_date to recalculate ETA
        $schedResult = $mysqli->query("SELECT scheduled_date FROM schedules WHERE id={$item['id']}");
        $schedRow = $schedResult->fetch_assoc();
        $scheduledDate = $schedRow['scheduled_date'];
        $estimatedDate = date('Y-m-d', strtotime($scheduledDate . ' +7 days'));
        
        $mysqli->query("UPDATE schedules SET priority_level='low', eta_days=7, estimated_date='{$estimatedDate}' WHERE id={$item['id']}");
        echo "  ✓ Updated schedule priority_level to 'low', eta_days=7, estimated_date={$estimatedDate}\n";
    }
    
    echo "\n";
}

echo "=== COMPLETE ===\n";
echo "All priorities have been set to 'low' for pending service submissions.\n";

$mysqli->close();
