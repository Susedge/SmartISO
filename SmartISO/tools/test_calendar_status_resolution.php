<?php
// Quick test script to demonstrate status resolution logic for calendar events.
// Simulates a schedule row with schedule.status = 'pending' but the underlying
// form_submissions.status = 'completed'. The calendar should show 'completed'.

// Simulated schedule row (e.g. from getSchedulesWithDetails)
$schedule = [
    'id' => 42,
    'submission_id' => 1001,
    'submission_status' => '', // missing or empty
    'status' => 'pending',     // schedule row still marked pending
    'scheduled_date' => '2025-11-25',
    'scheduled_time' => '09:00:00'
];

// Simulated authoritative lookup result from form_submissions
$submissionStatusMap = [
    1001 => 'completed'
];

// Old behavior (before fix): prefer submission_status then schedule.status then lookup map
$oldStatus = null;
if (!empty($schedule['submission_status'])) {
    $oldStatus = $schedule['submission_status'];
} elseif (!empty($schedule['status'])) {
    $oldStatus = $schedule['status'];
} elseif (!empty($schedule['submission_id'])) {
    $sid = (int)$schedule['submission_id'];
    if (isset($submissionStatusMap[$sid])) { $oldStatus = $submissionStatusMap[$sid]; }
}
$oldStatus = strtolower(trim($oldStatus ?? 'pending'));

// New behavior (after fix): if submission_id exists prefer authoritative submission.status, fall back to schedule.status
$newStatus = null;
if (!empty($schedule['submission_id'])) {
    $sid = (int)$schedule['submission_id'];
    if (isset($submissionStatusMap[$sid]) && !empty($submissionStatusMap[$sid])) {
        $newStatus = $submissionStatusMap[$sid];
    } elseif (!empty($schedule['submission_status'])) {
        $newStatus = $schedule['submission_status'];
    } elseif (!empty($schedule['status'])) {
        $newStatus = $schedule['status'];
    }
} else {
    if (!empty($schedule['status'])) { $newStatus = $schedule['status']; }
}
$newStatus = strtolower(trim($newStatus ?? 'pending'));

echo "Simulated schedule row:\n" . print_r($schedule, true) . "\n";
echo "Authoritative submission map:\n" . print_r($submissionStatusMap, true) . "\n";

echo "OLD resolved status (pre-fix): " . $oldStatus . "\n";
echo "NEW resolved status (post-fix): " . $newStatus . "\n";

if ($newStatus !== 'completed') {
    echo "ERROR: new behavior did not resolve to completed — investigate further.\n";
    exit(1);
}

echo "SUCCESS: calendar status resolves to authoritative submission.status (completed).\n";
