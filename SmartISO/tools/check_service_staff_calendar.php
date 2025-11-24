<?php
// CLI diagnostic script to check service staff calendar data
// Usage: php tools/check_service_staff_calendar.php <staff_id>

require __DIR__ . '/../vendor/autoload.php';

$staffId = $argv[1] ?? null;
if (!$staffId) {
    echo "Usage: php tools/check_service_staff_calendar.php <staff_id>\n";
    exit(1);
}

// Bootstrap CodeIgniter environment minimally
chdir(__DIR__ . '/..');
// Attempt to load app config
if (!class_exists('\Config\Database')) {
    echo "Failed to find CodeIgniter environment - run this from project root.\n";
    exit(1);
}

$db = \Config\Database::connect();

// Find submissions assigned to staff
$builder = $db->table('form_submissions fs');
$builder->select('fs.id as submission_id, fs.status, fs.approved_at, fs.service_staff_id, s.id as schedule_id, s.assigned_staff_id')
    ->join('schedules s', 's.submission_id = fs.id', 'left')
    ->where('fs.service_staff_id', (int)$staffId)
    ->orderBy('fs.approved_at', 'DESC');

$rows = $builder->get()->getResultArray();

echo "Service Staff ID: {$staffId}\n";
echo "Total submissions found: " . count($rows) . "\n\n";

$missingInCalendar = [];
$countSchedules = 0;
$countVirtual = 0;

foreach ($rows as $r) {
    $hasScheduleRow = !empty($r['schedule_id']);
    $schedAssigned = $r['assigned_staff_id'] ?? null;
    echo "Submission {$r['submission_id']}: status={$r['status']}, approved_at={$r['approved_at']}, schedule_id=" . ($r['schedule_id'] ?? 'NONE') . ", schedule.assigned_staff_id=" . ($schedAssigned ?? 'NULL') . "\n";

    // In the old bug, if schedule exists but schedule.assigned_staff_id != staffId,
    // getStaffSchedules would not return it and getServiceStaffSubmissionsWithoutSchedules
    // excluded it if NOT EXISTS check was present. This script flags those.
    if ($hasScheduleRow && $schedAssigned != $staffId) {
        $missingInCalendar[] = $r['submission_id'];
    }

    if ($hasScheduleRow) $countSchedules++;
    else $countVirtual++;
}

echo "\nSummary:\n";
echo "Schedules rows: {$countSchedules}\n";
echo "Submissions without schedule row: {$countVirtual}\n";
if (!empty($missingInCalendar)) {
    echo "Potentially missing on calendar (schedule exists but not assigned to staff): " . implode(', ', $missingInCalendar) . "\n";
} else {
    echo "No obviously-missing items found for this staff.\n";
}

echo "\nNOTE: Run the calendar page as THAT staff user and check the calendar for missing items.\n";
