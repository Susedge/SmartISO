<?php
// Debug schedule visibility for department admins

$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=================================================================\n";
echo "SCHEDULE VISIBILITY DEBUG\n";
echo "=================================================================\n\n";

// Get department admin
$stmt = $pdo->query("
    SELECT u.id, u.full_name, u.department_id, d.description as dept_name
    FROM users u
    LEFT JOIN departments d ON d.id = u.department_id
    WHERE u.user_type = 'department_admin' AND u.active = 1
    LIMIT 1
");
$deptAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$deptAdmin) {
    echo "✗ No department admins found!\n";
    exit(1);
}

echo "Department Admin: {$deptAdmin['full_name']}\n";
echo "Department: {$deptAdmin['dept_name']} (ID: {$deptAdmin['department_id']})\n\n";

// Check what getDepartmentSchedules query returns
echo "=== WHAT getDepartmentSchedules() SHOULD RETURN ===\n";
echo "Query: WHERE submitter.department_id = {$deptAdmin['department_id']}\n\n";

$stmt = $pdo->prepare("
    SELECT s.id as schedule_id,
           s.submission_id,
           fs.form_id,
           f.code as form_code,
           f.department_id as form_dept_id,
           fd.description as form_dept_name,
           submitter.id as submitter_id,
           submitter.full_name as submitter_name,
           submitter.department_id as submitter_dept_id,
           sd.description as submitter_dept_name
    FROM schedules s
    LEFT JOIN form_submissions fs ON fs.id = s.submission_id
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN departments fd ON fd.id = f.department_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    LEFT JOIN departments sd ON sd.id = submitter.department_id
    WHERE submitter.department_id = ?
    ORDER BY s.scheduled_date DESC
    LIMIT 10
");
$stmt->execute([$deptAdmin['department_id']]);
$correctSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($correctSchedules) . " schedule(s) for this department:\n\n";

if (empty($correctSchedules)) {
    echo "  (No schedules from this department's requestors)\n";
} else {
    foreach ($correctSchedules as $sched) {
        echo "  Schedule ID: {$sched['schedule_id']}\n";
        echo "    Form: {$sched['form_code']} (Form Dept: {$sched['form_dept_name']})\n";
        echo "    Submitter: {$sched['submitter_name']} (Submitter Dept: {$sched['submitter_dept_name']})\n\n";
    }
}

// Check ALL schedules
echo "\n=== ALL SCHEDULES IN SYSTEM ===\n";
$stmt = $pdo->query("
    SELECT s.id as schedule_id,
           s.submission_id,
           f.code as form_code,
           f.department_id as form_dept_id,
           fd.description as form_dept_name,
           submitter.full_name as submitter_name,
           submitter.department_id as submitter_dept_id,
           sd.description as submitter_dept_name
    FROM schedules s
    LEFT JOIN form_submissions fs ON fs.id = s.submission_id
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN departments fd ON fd.id = f.department_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    LEFT JOIN departments sd ON sd.id = submitter.department_id
    ORDER BY s.scheduled_date DESC
    LIMIT 10
");
$allSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total schedules (last 10):\n\n";

$wrongCount = 0;
foreach ($allSchedules as $sched) {
    $isCorrectDept = ($sched['submitter_dept_id'] == $deptAdmin['department_id']);
    $marker = $isCorrectDept ? '✓' : '✗';
    
    if (!$isCorrectDept) {
        $wrongCount++;
    }
    
    echo "  {$marker} Schedule ID: {$sched['schedule_id']}\n";
    echo "     Form: {$sched['form_code']} (Form Dept: {$sched['form_dept_name']} - ID: {$sched['form_dept_id']})\n";
    echo "     Submitter: {$sched['submitter_name']} (Submitter Dept: {$sched['submitter_dept_name']} - ID: {$sched['submitter_dept_id']})\n";
    echo "     Admin's Dept: {$deptAdmin['dept_name']} (ID: {$deptAdmin['department_id']})\n\n";
}

echo "\n=== ANALYSIS ===\n";
echo "Department Admin Department: {$deptAdmin['dept_name']} (ID: {$deptAdmin['department_id']})\n";
echo "Schedules from correct department: " . count($correctSchedules) . "\n";
echo "Schedules from wrong department: {$wrongCount}\n\n";

if ($wrongCount > 0) {
    echo "⚠️  ISSUE: If department admin sees {$wrongCount} wrong schedule(s), filtering is NOT working!\n";
    echo "\nPOSSIBLE CAUSES:\n";
    echo "1. Form's department_id is being used instead of submitter's department_id\n";
    echo "2. Query is not applying the WHERE clause correctly\n";
    echo "3. Calendar is showing ALL schedules regardless of filtering\n";
} else {
    echo "✓ All schedules are from the correct department.\n";
}

echo "\n=================================================================\n";
