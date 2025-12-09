<?php
// Debug what's actually showing on the calendar for department admin

$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Checking Schedule Structure ===\n\n";

// Get a sample schedule with all fields
$query = "SELECT s.*, 
          fs.status as submission_status,
          f.form_name, f.code as form_code,
          u.id as requestor_id, u.full_name as requestor_name, u.department_id as requestor_department_id
          FROM schedules s
          LEFT JOIN form_submissions fs ON s.submission_id = fs.id
          LEFT JOIN forms f ON fs.form_id = f.id
          LEFT JOIN users u ON fs.user_id = u.id
          LIMIT 5";

$result = $pdo->query($query);
$schedules = $result->fetchAll(PDO::FETCH_ASSOC);

echo "Sample schedules (first 5):\n";
foreach ($schedules as $idx => $schedule) {
    echo "\n--- Schedule " . ($idx + 1) . " ---\n";
    echo "Schedule ID: " . $schedule['id'] . "\n";
    echo "Submission ID: " . $schedule['submission_id'] . "\n";
    echo "Form: " . $schedule['form_code'] . "\n";
    echo "Requestor Department ID: " . ($schedule['requestor_department_id'] ?? 'NULL') . "\n";
    echo "Status: " . $schedule['submission_status'] . "\n";
    echo "Keys in array: " . implode(', ', array_keys($schedule)) . "\n";
}

echo "\n=== Checking getDepartmentSchedules Query ===\n\n";

// Check what getDepartmentSchedules returns for dept 22
$query2 = "SELECT s.id as schedule_id, s.submission_id, s.schedule_date, s.schedule_time,
                  fs.status, f.form_name, f.code as form_code,
                  u.id as requestor_id, u.full_name as requestor_name, 
                  u.department_id as requestor_department_id,
                  GROUP_CONCAT(DISTINCT staff.full_name SEPARATOR ', ') as assigned_staff_names
           FROM schedules s
           LEFT JOIN form_submissions fs ON s.submission_id = fs.id
           LEFT JOIN forms f ON fs.form_id = f.id
           LEFT JOIN users u ON fs.user_id = u.id
           LEFT JOIN schedule_staff ss ON s.id = ss.schedule_id
           LEFT JOIN users staff ON ss.user_id = staff.id
           WHERE u.department_id = 22
           GROUP BY s.id
           ORDER BY s.schedule_date DESC, s.schedule_time DESC";

$result2 = $pdo->query($query2);
$deptSchedules = $result2->fetchAll(PDO::FETCH_ASSOC);

echo "Schedules for department 22 (IT): " . count($deptSchedules) . "\n";
foreach ($deptSchedules as $idx => $schedule) {
    echo "  " . ($idx + 1) . ". Submission ID: " . $schedule['submission_id'] . " | Form: " . $schedule['form_code'] . " | Dept ID: " . $schedule['requestor_department_id'] . "\n";
}

echo "\n=== Checking All Schedules (No Filter) ===\n\n";

$query3 = "SELECT s.id as schedule_id, s.submission_id,
                  u.department_id as requestor_department_id,
                  f.code as form_code,
                  fs.status
           FROM schedules s
           LEFT JOIN form_submissions fs ON s.submission_id = fs.id
           LEFT JOIN forms f ON fs.form_id = f.id
           LEFT JOIN users u ON fs.user_id = u.id
           ORDER BY s.id DESC
           LIMIT 20";

$result3 = $pdo->query($query3);
$allSchedules = $result3->fetchAll(PDO::FETCH_ASSOC);

echo "All schedules (last 20):\n";
foreach ($allSchedules as $idx => $schedule) {
    echo "  Schedule ID: " . $schedule['schedule_id'] . " | Submission: " . $schedule['submission_id'] . " | Form: " . $schedule['form_code'] . " | Dept: " . $schedule['requestor_department_id'] . " | Status: " . $schedule['status'] . "\n";
}

echo "\n=== Field Existence Check ===\n\n";
if (!empty($allSchedules)) {
    $firstSchedule = $allSchedules[0];
    echo "Field 'requestor_department_id' exists: " . (isset($firstSchedule['requestor_department_id']) ? "YES" : "NO") . "\n";
    echo "Value: " . ($firstSchedule['requestor_department_id'] ?? 'NULL') . "\n";
}
