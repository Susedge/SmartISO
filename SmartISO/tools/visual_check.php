<?php
// Quick visual check
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');
$result = $mysqli->query("
    SELECT s.id, s.submission_id, s.scheduled_date, s.priority_level, s.eta_days, s.estimated_date, 
           f.code, DATE(fs.approved_at) as approval_date
    FROM schedules s 
    INNER JOIN form_submissions fs ON fs.id = s.submission_id 
    INNER JOIN forms f ON f.id = fs.form_id 
    WHERE s.id IN (12, 14)
    ORDER BY s.id DESC
");

echo "=== VISUAL CHECK - LATEST APPROVALS ===\n\n";

while ($row = $result->fetch_assoc()) {
    echo "Schedule {$row['id']} (Submission {$row['submission_id']}):\n";
    echo "  Form: {$row['code']}\n";
    echo "  Approval Date: {$row['approval_date']}\n";
    echo "  Scheduled Date: {$row['scheduled_date']}\n";
    echo "  Priority: {$row['priority_level']}\n";
    echo "  ETA Days: {$row['eta_days']}\n";
    echo "  Estimated Completion: {$row['estimated_date']}\n\n";
    
    if ($row['priority_level'] == 'low') {
        echo "  (Low priority = 7 days from approval)\n";
    } elseif ($row['priority_level'] == 'medium') {
        echo "  (Medium priority = 5 days from approval)\n";
    }
    echo "\n";
}
$mysqli->close();
