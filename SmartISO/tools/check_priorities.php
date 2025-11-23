<?php
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

echo "=== CHECKING PRIORITIES IN DATABASE ===\n\n";

echo "1. Form Submissions Priority:\n";
$result = $mysqli->query("SELECT id, form_id, status, priority, service_staff_id FROM form_submissions WHERE status IN ('approved', 'pending_service') ORDER BY id DESC LIMIT 10");
while ($row = $result->fetch_assoc()) {
    echo "   Submission ID: {$row['id']}, Form ID: {$row['form_id']}, Status: {$row['status']}, Priority: {$row['priority']}, Service Staff: {$row['service_staff_id']}\n";
}

echo "\n2. Schedules Priority Level:\n";
$result = $mysqli->query("SELECT s.id, s.submission_id, s.priority_level, s.eta_days, fs.priority as submission_priority FROM schedules s JOIN form_submissions fs ON s.submission_id = fs.id WHERE fs.status IN ('approved', 'pending_service') ORDER BY s.id DESC LIMIT 10");
while ($row = $result->fetch_assoc()) {
    echo "   Schedule ID: {$row['id']}, Submission ID: {$row['submission_id']}, Priority Level: {$row['priority_level']}, ETA Days: {$row['eta_days']}, Submission Priority: {$row['submission_priority']}\n";
}

echo "\n3. Priority Mismatch Check (non-low priorities):\n";
$result = $mysqli->query("SELECT fs.id, fs.form_id, fs.priority as sub_priority, s.priority_level as sched_priority FROM form_submissions fs LEFT JOIN schedules s ON s.submission_id = fs.id WHERE fs.status IN ('approved', 'pending_service')");
$count = 0;
while ($row = $result->fetch_assoc()) {
    $subPriority = $row['sub_priority'] ?? 'NULL';
    $schedPriority = $row['sched_priority'] ?? 'NULL';
    if ($subPriority !== 'low' || $schedPriority !== 'low') {
        echo "   - Submission {$row['id']} (Form {$row['form_id']}): submission.priority={$subPriority}, schedule.priority_level={$schedPriority}\n";
        $count++;
    }
}
echo "   Total found: $count submissions with non-low priority\n";

$mysqli->close();
