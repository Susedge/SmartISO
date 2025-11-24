<?php
// Quick diagnostic: find serviced submissions that may be missing a "Service Completed" notification
// Usage: php tools/diagnose_service_notifications.php

$dsn = 'mysql:host=localhost;dbname=smartiso;port=3306';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    echo "Failed to connect to DB: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo "Service notification diagnostic\n";
echo str_repeat('=', 60) . "\n";

$sql = "
SELECT fs.id as submission_id, fs.submitted_by, fs.service_staff_id, fs.service_staff_signature_date, fs.completion_date,
       u.full_name as requestor_name, ss.full_name as service_staff_name,
       EXISTS(SELECT 1 FROM notifications n WHERE n.submission_id = fs.id AND n.title LIKE '%Service Completed%') as has_service_completed_notif
FROM form_submissions fs
LEFT JOIN users u ON u.id = fs.submitted_by
LEFT JOIN users ss ON ss.id = fs.service_staff_id
WHERE fs.service_staff_signature_date IS NOT NULL
  AND fs.completed = 1
ORDER BY fs.completion_date DESC
LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "No recently serviced submissions found (or DB has no matching rows).\n";
    exit(0);
}

$missingCount = 0;
foreach ($rows as $r) {
    $has = (int)$r['has_service_completed_notif'];
    $status = $has ? 'OK' : 'MISSING';
    if (!$has) $missingCount++;
    printf("%s  Submission ID: %s | Requestor: %s | Service: %s | Completed: %s\n",
        str_pad($status, 8), $r['submission_id'], $r['requestor_name'] ?? 'N/A', $r['service_staff_name'] ?? 'N/A', $r['completion_date'] ?? 'N/A'
    );
}

echo "\nTotal checked: " . count($rows) . " - Missing notifications: " . $missingCount . "\n";

if ($missingCount > 0) {
    echo "\nTip: If you have missing rows, run the following SQL to create a notification for a specific submission (replace SUB_ID and USER_ID):\n";
    echo "INSERT INTO notifications (user_id, submission_id, title, message, read, created_at) VALUES (USER_ID, SUB_ID, 'Service Completed', 'Your service request has been completed successfully. You can now provide feedback about your experience.', 0, NOW());\n";
}

echo str_repeat('=', 60) . "\n";

exit(0);
