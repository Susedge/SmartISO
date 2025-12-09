<?php
// Debug what's actually showing on the calendar for department admin

$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=================================================================\n";
echo "INVESTIGATING CALENDAR DATA FOR DEPARTMENT ADMIN\n";
echo "=================================================================\n\n";

// Get IT dept admin
$stmt = $pdo->query("
    SELECT u.id, u.username, u.full_name, u.department_id, d.description as dept_name
    FROM users u
    LEFT JOIN departments d ON d.id = u.department_id
    WHERE u.user_type = 'department_admin' AND u.active = 1
    LIMIT 1
");
$deptAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Department Admin: {$deptAdmin['full_name']} (ID: {$deptAdmin['id']})\n";
echo "Department: {$deptAdmin['dept_name']} (ID: {$deptAdmin['department_id']})\n\n";

// Check getDepartmentSchedules results
echo "=== getDepartmentSchedules({$deptAdmin['department_id']}) ===\n";
$stmt = $pdo->prepare("
    SELECT s.id as schedule_id,
           s.submission_id,
           fs.form_id,
           f.code as form_code,
           f.department_id as form_dept_id,
           submitter.id as submitter_id,
           submitter.full_name as submitter_name,
           submitter.department_id as submitter_dept_id,
           sd.description as submitter_dept_name
    FROM schedules s
    LEFT JOIN form_submissions fs ON fs.id = s.submission_id
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    LEFT JOIN departments sd ON sd.id = submitter.department_id
    WHERE submitter.department_id = ?
    ORDER BY s.scheduled_date DESC
");
$stmt->execute([$deptAdmin['department_id']]);
$deptSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Count: " . count($deptSchedules) . "\n\n";

if (empty($deptSchedules)) {
    echo "  (No schedules - correct!)\n";
} else {
    foreach ($deptSchedules as $sched) {
        echo "  Schedule ID: {$sched['schedule_id']}\n";
        echo "    Submitter: {$sched['submitter_name']} (Dept: {$sched['submitter_dept_name']})\n\n";
    }
}

// Check getDepartmentSubmissionsWithoutSchedules
echo "\n=== getDepartmentSubmissionsWithoutSchedules({$deptAdmin['department_id']}) ===\n";
$stmt = $pdo->prepare("
    SELECT fs.id as submission_id,
           fs.form_id,
           f.code as form_code,
           f.department_id as form_dept_id,
           submitter.full_name as submitter_name,
           submitter.department_id as submitter_dept_id,
           sd.description as submitter_dept_name
    FROM form_submissions fs
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    LEFT JOIN departments sd ON sd.id = submitter.department_id
    WHERE submitter.department_id = ?
    AND NOT EXISTS (SELECT 1 FROM schedules s WHERE s.submission_id = fs.id)
    AND fs.status IN ('submitted', 'approved', 'pending_service')
    ORDER BY fs.created_at DESC
");
$stmt->execute([$deptAdmin['department_id']]);
$submissionsWithout = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Count: " . count($submissionsWithout) . "\n\n";

if (empty($submissionsWithout)) {
    echo "  (No submissions without schedules - correct!)\n";
} else {
    foreach ($submissionsWithout as $sub) {
        echo "  Submission ID: {$sub['submission_id']}\n";
        echo "    Form: {$sub['form_code']}\n";
        echo "    Submitter: {$sub['submitter_name']} (Dept: {$sub['submitter_dept_name']})\n\n";
    }
}

// Now check what SHOULD NOT appear
echo "\n=== SUBMISSIONS FROM OTHER DEPARTMENTS (Should NOT appear) ===\n";
$stmt = $pdo->prepare("
    SELECT fs.id as submission_id,
           fs.status,
           f.code as form_code,
           f.department_id as form_dept_id,
           fd.description as form_dept_name,
           submitter.full_name as submitter_name,
           submitter.department_id as submitter_dept_id,
           sd.description as submitter_dept_name,
           CASE WHEN EXISTS(SELECT 1 FROM schedules s WHERE s.submission_id = fs.id) THEN 'HAS SCHEDULE' ELSE 'NO SCHEDULE' END as schedule_status
    FROM form_submissions fs
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN departments fd ON fd.id = f.department_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    LEFT JOIN departments sd ON sd.id = submitter.department_id
    WHERE submitter.department_id != ?
    AND fs.status IN ('submitted', 'approved', 'pending_service')
    ORDER BY fs.created_at DESC
    LIMIT 10
");
$stmt->execute([$deptAdmin['department_id']]);
$otherDeptSubmissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($otherDeptSubmissions) . " submission(s) from other departments:\n\n";

foreach ($otherDeptSubmissions as $sub) {
    echo "  Submission ID: {$sub['submission_id']} - {$sub['form_code']} ({$sub['schedule_status']})\n";
    echo "    Status: {$sub['status']}\n";
    echo "    Form Dept: {$sub['form_dept_name']} (ID: {$sub['form_dept_id']})\n";
    echo "    Submitter: {$sub['submitter_name']}\n";
    echo "    Submitter Dept: {$sub['submitter_dept_name']} (ID: {$sub['submitter_dept_id']})\n";
    echo "    ⚠️  This should NOT appear on {$deptAdmin['full_name']}'s calendar!\n\n";
}

echo "\n=== TOTAL EXPECTED ON CALENDAR ===\n";
$totalExpected = count($deptSchedules) + count($submissionsWithout);
echo "Schedules from dept: " . count($deptSchedules) . "\n";
echo "Submissions without schedule from dept: " . count($submissionsWithout) . "\n";
echo "Total expected: {$totalExpected}\n\n";

if ($totalExpected > 0) {
    echo "✓ Calendar should show {$totalExpected} item(s)\n";
} else {
    echo "✓ Calendar should be EMPTY\n";
}

echo "\n=== ACTUAL CALENDAR CHECK ===\n";
echo "If you see 5 submissions on the calendar, they are likely:\n";
echo "1. Coming from getDepartmentSubmissionsWithoutSchedules() incorrectly\n";
echo "2. Being added by some other code path\n";
echo "3. Not being filtered properly in the view\n\n";

echo "Please check the calendar page source and network tab to see where the 5 items are coming from.\n";

echo "\n=================================================================\n";
