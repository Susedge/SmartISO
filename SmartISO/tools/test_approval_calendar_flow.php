<?php
/**
 * Test the complete flow: Approval → Schedule Creation → Calendar Display
 */

// Direct MySQLi connection
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "=== TESTING APPROVAL → CALENDAR FLOW ===\n\n";

// Service Staff ID to test with
$serviceStaffId = 5;

echo "1. Getting all submissions assigned to Service Staff ID {$serviceStaffId}:\n";
$result = $mysqli->query("
    SELECT 
        fs.id,
        fs.status,
        fs.service_staff_id,
        fs.approved_at,
        f.code as form_code
    FROM form_submissions fs
    LEFT JOIN forms f ON f.id = fs.form_id
    WHERE fs.service_staff_id = {$serviceStaffId}
    ORDER BY fs.id DESC
");

$submissions = $result->fetch_all(MYSQLI_ASSOC);
echo "   Found " . count($submissions) . " submission(s) assigned to this service staff\n\n";

foreach ($submissions as $sub) {
    echo "   Submission {$sub['id']} ({$sub['form_code']}):\n";
    echo "     Status: {$sub['status']}\n";
    echo "     Approved: " . ($sub['approved_at'] ?? 'Not yet') . "\n";
    
    // Check schedule
    $schedStmt = $mysqli->prepare("SELECT id, assigned_staff_id, status FROM schedules WHERE submission_id = ?");
    $schedStmt->bind_param('i', $sub['id']);
    $schedStmt->execute();
    $schedResult = $schedStmt->get_result();
    $schedule = $schedResult->fetch_assoc();
    
    if ($schedule) {
        echo "     Schedule: EXISTS (ID: {$schedule['id']}, Status: {$schedule['status']})\n";
        echo "     Schedule assigned_staff_id: " . ($schedule['assigned_staff_id'] ?? 'NULL') . "\n";
        
        if ($schedule['assigned_staff_id'] == $serviceStaffId) {
            echo "     ✓ Will appear on calendar\n";
        } else {
            echo "     ✗ PROBLEM: assigned_staff_id doesn't match!\n";
        }
    } else {
        echo "     Schedule: NONE - will appear as virtual schedule\n";
        echo "     ✓ Will appear on calendar\n";
    }
    echo "\n";
}

echo "\n2. Testing ScheduleModel::getStaffSchedules() query:\n";
echo "   This is what the calendar actually queries...\n\n";

$result = $mysqli->query("
    SELECT 
        s.id as schedule_id,
        s.submission_id,
        s.assigned_staff_id,
        s.status as schedule_status,
        fs.status as submission_status,
        f.code as form_code
    FROM schedules s
    LEFT JOIN form_submissions fs ON fs.id = s.submission_id
    LEFT JOIN forms f ON f.id = fs.form_id
    WHERE s.assigned_staff_id = {$serviceStaffId}
    ORDER BY s.id DESC
");

$calendarSchedules = $result->fetch_all(MYSQLI_ASSOC);
echo "   ✓ Calendar will show " . count($calendarSchedules) . " schedule(s) via getStaffSchedules()\n\n";

if (empty($calendarSchedules)) {
    echo "   ✗ WARNING: No schedules found! Calendar will be empty!\n";
} else {
    foreach ($calendarSchedules as $sched) {
        echo "   Schedule {$sched['schedule_id']} → Submission {$sched['submission_id']} ({$sched['form_code']})\n";
        echo "     Schedule Status: {$sched['schedule_status']}\n";
        echo "     Submission Status: {$sched['submission_status']}\n";
    }
}

echo "\n\n3. Testing getServiceStaffSubmissionsWithoutSchedules() query:\n";
echo "   This adds submissions without schedule entries...\n\n";

$result = $mysqli->query("
    SELECT 
        fs.id as submission_id,
        fs.status,
        f.code as form_code,
        s.id as schedule_id
    FROM form_submissions fs
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN schedules s ON s.submission_id = fs.id
    WHERE fs.service_staff_id = {$serviceStaffId}
      AND fs.status IN ('approved', 'pending_service', 'completed')
    ORDER BY fs.id DESC
");

$allSubmissions = $result->fetch_all(MYSQLI_ASSOC);
$withoutSchedule = array_filter($allSubmissions, function($s) { return empty($s['schedule_id']); });

echo "   Total submissions: " . count($allSubmissions) . "\n";
echo "   Without schedules: " . count($withoutSchedule) . "\n";
echo "   ✓ Calendar will show " . count($withoutSchedule) . " virtual schedule(s)\n\n";

if (count($withoutSchedule) > 0) {
    foreach ($withoutSchedule as $sub) {
        echo "   Virtual Schedule for Submission {$sub['submission_id']} ({$sub['form_code']})\n";
        echo "     Status: {$sub['status']}\n";
    }
}

echo "\n\n4. FINAL CALENDAR SUMMARY:\n";
$totalOnCalendar = count($calendarSchedules) + count($withoutSchedule);
echo "   Real Schedules: " . count($calendarSchedules) . "\n";
echo "   Virtual Schedules: " . count($withoutSchedule) . "\n";
echo "   TOTAL on Calendar: {$totalOnCalendar}\n";

if ($totalOnCalendar > 0) {
    echo "\n   ✓ SUCCESS: Service staff calendar should show {$totalOnCalendar} event(s)\n";
} else {
    echo "\n   ✗ PROBLEM: Calendar will be empty!\n";
}

echo "\n\n5. Checking pending service page:\n";
$result = $mysqli->query("
    SELECT COUNT(*) as count
    FROM form_submissions
    WHERE service_staff_id = {$serviceStaffId}
      AND status IN ('pending_service', 'approved')
");
$row = $result->fetch_assoc();
echo "   Pending service page should show: {$row['count']} submission(s)\n";

echo "\n=== TEST COMPLETE ===\n";
$mysqli->close();
