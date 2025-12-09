<?php
// Check calendar filtering issue for department admins

$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=================================================================\n";
echo "DEPARTMENT ADMIN CALENDAR ISSUE DIAGNOSTIC\n";
echo "=================================================================\n\n";

// Get CRSRF form signatories
echo "=== CRSRF FORM SIGNATORIES ===\n";
$stmt = $pdo->query("
    SELECT fs.*, u.full_name, u.user_type, u.department_id, d.description as dept_name
    FROM form_signatories fs
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users u ON u.id = fs.user_id
    LEFT JOIN departments d ON d.id = u.department_id
    WHERE f.code = 'CRSRF'
");
$signatories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($signatories)) {
    echo "  No signatories assigned to CRSRF form.\n";
} else {
    foreach ($signatories as $sig) {
        echo "  - {$sig['full_name']} ({$sig['user_type']}) - Dept: {$sig['dept_name']} (ID: {$sig['department_id']})\n";
    }
}

// Get department admin
echo "\n=== DEPARTMENT ADMIN SAMPLE ===\n";
$stmt = $pdo->query("
    SELECT u.id, u.full_name, u.user_type, u.department_id, d.description as dept_name
    FROM users u
    LEFT JOIN departments d ON d.id = u.department_id
    WHERE u.user_type = 'department_admin' AND u.active = 1
    LIMIT 1
");
$deptAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$deptAdmin) {
    echo "  ✗ No department admins found!\n";
    exit(1);
}

echo "  Found: {$deptAdmin['full_name']} (ID: {$deptAdmin['id']}) - Dept: {$deptAdmin['dept_name']} (ID: {$deptAdmin['department_id']})\n";

// Check what schedules this dept admin would see using getDepartmentSchedules logic
echo "\n=== SCHEDULES FROM THIS DEPT ADMIN'S DEPARTMENT ===\n";
echo "Using query: schedules WHERE requestor.department_id = {$deptAdmin['department_id']}\n\n";

$stmt = $pdo->prepare("
    SELECT s.id, s.submission_id, s.scheduled_date, s.status,
           fs.form_id, fs.panel_name, fs.status as submission_status,
           f.code as form_code, f.description as form_description,
           u.full_name as requestor_name, u.department_id as requestor_department_id,
           d.description as requestor_dept_name,
           staff.full_name as assigned_staff_name
    FROM schedules s
    LEFT JOIN form_submissions fs ON fs.id = s.submission_id
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users u ON u.id = fs.submitted_by
    LEFT JOIN departments d ON d.id = u.department_id
    LEFT JOIN users staff ON staff.id = s.assigned_staff_id
    WHERE u.department_id = ?
    ORDER BY s.scheduled_date ASC
    LIMIT 10
");
$stmt->execute([$deptAdmin['department_id']]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($schedules)) {
    echo "  No schedules found for this department.\n";
} else {
    echo "  Found " . count($schedules) . " schedule(s):\n\n";
    foreach ($schedules as $sched) {
        echo "  Schedule ID: {$sched['id']} | Submission: {$sched['submission_id']}\n";
        echo "    Form: {$sched['form_code']} - {$sched['form_description']}\n";
        echo "    Requestor: {$sched['requestor_name']} (Dept: {$sched['requestor_dept_name']})\n";
        echo "    Date: {$sched['scheduled_date']} | Status: {$sched['status']}\n\n";
    }
}

// Check ALL schedules (what admin would see)
echo "\n=== ALL SCHEDULES IN SYSTEM (What dept admin might incorrectly see) ===\n";
$stmt = $pdo->query("
    SELECT s.id, s.submission_id, s.scheduled_date,
           f.code as form_code,
           u.full_name as requestor_name, u.department_id as requestor_dept_id,
           d.description as requestor_dept_name
    FROM schedules s
    LEFT JOIN form_submissions fs ON fs.id = s.submission_id
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users u ON u.id = fs.submitted_by
    LEFT JOIN departments d ON d.id = u.department_id
    ORDER BY s.scheduled_date DESC
    LIMIT 10
");
$allSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "  Total schedules in system (last 10):\n\n";
$wrongDeptCount = 0;
foreach ($allSchedules as $sched) {
    $match = ($sched['requestor_dept_id'] == $deptAdmin['department_id']) ? '✓' : '✗';
    if ($match === '✗') $wrongDeptCount++;
    
    echo "  {$match} Schedule ID: {$sched['id']} | {$sched['form_code']}\n";
    echo "     Requestor: {$sched['requestor_name']} (Dept: {$sched['requestor_dept_name']})\n\n";
}

if ($wrongDeptCount > 0) {
    echo "\n⚠️  If dept admin sees all {$wrongDeptCount} schedule(s) from other departments, calendar filtering is broken!\n";
} else {
    echo "\n✓ All schedules are from the correct department.\n";
}

echo "\n=================================================================\n";
echo "DIAGNOSIS COMPLETE\n";
echo "=================================================================\n";
