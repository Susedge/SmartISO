<?php
/**
 * Direct check of schedules table for service staff user
 */

$host = 'localhost';
$database = 'smartiso';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

echo "========================================\n";
echo "DIRECT SCHEDULES TABLE CHECK\n";
echo "========================================\n\n";

$userId = 5;

// Get schedules WHERE assigned_staff_id = 5 (the condition used by calendar)
echo "=== METHOD 1: Calendar Query (assigned_staff_id = $userId) ===\n";
$stmt = $pdo->prepare("
    SELECT s.*, fs.status as submission_status 
    FROM schedules s
    LEFT JOIN form_submissions fs ON fs.id = s.submission_id
    WHERE s.assigned_staff_id = ?
    ORDER BY s.scheduled_date DESC
");
$stmt->execute([$userId]);
$calendarSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found: " . count($calendarSchedules) . " schedules\n\n";
if (!empty($calendarSchedules)) {
    foreach ($calendarSchedules as $sched) {
        echo "  Schedule ID: {$sched['id']}\n";
        echo "    Submission: {$sched['submission_id']}\n";
        echo "    Assigned Staff: {$sched['assigned_staff_id']}\n";
        echo "    Date: {$sched['scheduled_date']} {$sched['scheduled_time']}\n";
        echo "    Schedule Status: {$sched['status']}\n";
        echo "    Submission Status: {$sched['submission_status']}\n\n";
    }
}

// Get schedules via submission service_staff_id
echo "=== METHOD 2: Via Submissions (service_staff_id = $userId) ===\n";
$stmt = $pdo->prepare("
    SELECT s.*, fs.service_staff_id, fs.status as submission_status
    FROM schedules s
    LEFT JOIN form_submissions fs ON fs.id = s.submission_id
    WHERE fs.service_staff_id = ?
    ORDER BY s.scheduled_date DESC
");
$stmt->execute([$userId]);
$submissionSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found: " . count($submissionSchedules) . " schedules\n\n";
if (!empty($submissionSchedules)) {
    foreach ($submissionSchedules as $sched) {
        echo "  Schedule ID: {$sched['id']}\n";
        echo "    Submission: {$sched['submission_id']}\n";
        echo "    Assigned Staff (schedule): {$sched['assigned_staff_id']}\n";
        echo "    Service Staff (submission): {$sched['service_staff_id']}\n";
        echo "    Date: {$sched['scheduled_date']} {$sched['scheduled_time']}\n";
        echo "    Schedule Status: {$sched['status']}\n";
        echo "    Submission Status: {$sched['submission_status']}\n\n";
    }
}

// Check actual submissions for user
echo "=== METHOD 3: Submissions Assigned to User ===\n";
$stmt = $pdo->prepare("
    SELECT fs.id, fs.status, fs.service_staff_id, f.description
    FROM form_submissions fs
    JOIN forms f ON f.id = fs.form_id
    WHERE fs.service_staff_id = ?
    ORDER BY fs.updated_at DESC
");
$stmt->execute([$userId]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found: " . count($submissions) . " submissions\n\n";
foreach ($submissions as $sub) {
    echo "  Submission ID: {$sub['id']}\n";
    echo "    Form: {$sub['description']}\n";
    echo "    Status: {$sub['status']}\n";
    
    // Check if this submission has a schedule
    $stmt2 = $pdo->prepare("SELECT id, assigned_staff_id FROM schedules WHERE submission_id = ?");
    $stmt2->execute([$sub['id']]);
    $sched = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if ($sched) {
        echo "    ✓ Has schedule: ID {$sched['id']}, assigned_staff_id = {$sched['assigned_staff_id']}\n";
    } else {
        echo "    ✗ NO SCHEDULE\n";
    }
    echo "\n";
}

echo "========================================\n";
echo "DIAGNOSIS\n";
echo "========================================\n\n";

if (count($calendarSchedules) === 0 && count($submissionSchedules) > 0) {
    echo "⚠ ISSUE FOUND:\n";
    echo "  - Calendar query (assigned_staff_id) returns: " . count($calendarSchedules) . "\n";
    echo "  - Submission query (service_staff_id) returns: " . count($submissionSchedules) . "\n\n";
    echo "ROOT CAUSE:\n";
    echo "  schedules.assigned_staff_id ≠ form_submissions.service_staff_id\n\n";
    echo "EXPLANATION:\n";
    echo "  - form_submissions.service_staff_id = $userId (correct)\n";
    echo "  - schedules.assigned_staff_id = different value (wrong!)\n\n";
    echo "SOLUTION:\n";
    echo "  Update schedules to match:\n";
    echo "  UPDATE schedules s\n";
    echo "  JOIN form_submissions fs ON fs.id = s.submission_id\n";
    echo "  SET s.assigned_staff_id = fs.service_staff_id\n";
    echo "  WHERE fs.service_staff_id = $userId\n";
}
