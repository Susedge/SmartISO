<?php
// Check what appears on both schedule routes

$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=================================================================\n";
echo "SCHEDULE ROUTES COMPARISON FOR DEPARTMENT ADMIN\n";
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

echo "Testing User: {$deptAdmin['full_name']} (ID: {$deptAdmin['id']})\n";
echo "Department: {$deptAdmin['dept_name']} (ID: {$deptAdmin['department_id']})\n\n";

// Route 1: /schedule (index method) - uses scoped_department_id
echo "═══════════════════════════════════════════════════════════════\n";
echo "ROUTE 1: /schedule (Schedule::index)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Code path: elseif (\$isDepartmentAdmin)\n";
echo "  Uses: session('scoped_department_id')\n";
echo "  Calls: getDepartmentSchedules(scoped_department_id)\n\n";

// Simulate what index() would return
$stmt = $pdo->prepare("
    SELECT s.id, s.submission_id, f.code, submitter.department_id as submitter_dept
    FROM schedules s
    LEFT JOIN form_submissions fs ON fs.id = s.submission_id
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    WHERE submitter.department_id = ?
");
$stmt->execute([$deptAdmin['department_id']]);
$indexSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT fs.id, f.code, submitter.department_id as submitter_dept
    FROM form_submissions fs
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    WHERE submitter.department_id = ?
    AND NOT EXISTS (SELECT 1 FROM schedules s WHERE s.submission_id = fs.id)
    AND fs.status IN ('submitted', 'approved', 'pending_service')
");
$stmt->execute([$deptAdmin['department_id']]);
$indexSubmissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$indexTotal = count($indexSchedules) + count($indexSubmissions);
echo "Expected items on /schedule: {$indexTotal}\n";
echo "  - Schedules: " . count($indexSchedules) . "\n";
echo "  - Submissions without schedules: " . count($indexSubmissions) . "\n\n";

// Route 2: /schedule/calendar (calendar method)
echo "═══════════════════════════════════════════════════════════════\n";
echo "ROUTE 2: /schedule/calendar (Schedule::calendar)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Code path: elseif (\$userType === 'department_admin')\n";
echo "  Uses: session('department_id')\n";
echo "  Calls: getDepartmentSchedules(department_id)\n\n";

// Same queries but for clarity
$stmt = $pdo->prepare("
    SELECT s.id, s.submission_id, f.code, submitter.department_id as submitter_dept
    FROM schedules s
    LEFT JOIN form_submissions fs ON fs.id = s.submission_id
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    WHERE submitter.department_id = ?
");
$stmt->execute([$deptAdmin['department_id']]);
$calendarSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT fs.id, f.code, submitter.department_id as submitter_dept
    FROM form_submissions fs
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    WHERE submitter.department_id = ?
    AND NOT EXISTS (SELECT 1 FROM schedules s WHERE s.submission_id = fs.id)
    AND fs.status IN ('submitted', 'approved', 'pending_service')
");
$stmt->execute([$deptAdmin['department_id']]);
$calendarSubmissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$calendarTotal = count($calendarSchedules) + count($calendarSubmissions);
echo "Expected items on /schedule/calendar: {$calendarTotal}\n";
echo "  - Schedules: " . count($calendarSchedules) . "\n";
echo "  - Submissions without schedules: " . count($calendarSubmissions) . "\n\n";

// Check for cross-department items
echo "═══════════════════════════════════════════════════════════════\n";
echo "CHECKING FOR CROSS-DEPARTMENT ITEMS (The 5 Submissions)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get 'submitted' status items from other departments (most likely to show as "pending")
$stmt = $pdo->prepare("
    SELECT fs.id, fs.status, f.code, f.description,
           submitter.full_name as submitter,
           submitter.department_id as submitter_dept,
           sd.description as submitter_dept_name,
           CASE WHEN EXISTS(SELECT 1 FROM schedules s WHERE s.submission_id = fs.id) THEN 'YES' ELSE 'NO' END as has_schedule
    FROM form_submissions fs
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    LEFT JOIN departments sd ON sd.id = submitter.department_id
    LEFT JOIN form_signatories fsig ON fsig.form_id = f.id AND fsig.user_id = ?
    WHERE submitter.department_id != ?
    AND fs.status = 'submitted'
    AND fsig.user_id IS NOT NULL
    ORDER BY fs.created_at DESC
    LIMIT 10
");
$stmt->execute([$deptAdmin['id'], $deptAdmin['department_id']]);
$crossDeptAsSignatory = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Submissions from OTHER departments where dept admin IS a signatory:\n";
echo "Count: " . count($crossDeptAsSignatory) . "\n\n";

if (!empty($crossDeptAsSignatory)) {
    foreach ($crossDeptAsSignatory as $item) {
        echo "  Submission ID: {$item['id']} - {$item['code']}\n";
        echo "    Status: {$item['status']}\n";
        echo "    Submitter: {$item['submitter']} (Dept: {$item['submitter_dept_name']})\n";
        echo "    Has Schedule: {$item['has_schedule']}\n";
        echo "    ⚠️  LIKELY APPEARING ON CALENDAR (signatory-based)\n\n";
    }
    
    echo "⚠️  ISSUE IDENTIFIED!\n";
    echo "These are showing because dept admin is a SIGNATORY,\n";
    echo "but the user wants department-based filtering only.\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "SOLUTION\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "The department admin should NOT see cross-department submissions,\n";
echo "even if they are a signatory on those forms.\n\n";

echo "Current behavior: Signatory status may override department filtering\n";
echo "Desired behavior: Department filtering should be ABSOLUTE for dept admins\n\n";

echo "Action needed: Ensure calendar() method does NOT include signatory-based\n";
echo "submissions for department_admin user type.\n\n";

echo "=================================================================\n";
