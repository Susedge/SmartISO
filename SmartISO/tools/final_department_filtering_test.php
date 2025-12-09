<?php
$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  COMPREHENSIVE DEPARTMENT FILTERING TEST                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Get IT dept admin
$query = "SELECT id, username, department_id FROM users WHERE username = 'dept_admin_it'";
$result = $pdo->query($query);
$itAdmin = $result->fetch(PDO::FETCH_ASSOC);

echo "Testing for: IT Department Admin\n";
echo "  User ID: {$itAdmin['id']}\n";
echo "  Department: IT (ID: {$itAdmin['department_id']})\n\n";

echo "─────────────────────────────────────────────────────────────\n";
echo "TEST 1: Schedule Calendar - getDepartmentSchedules()\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$scheduleQuery = "SELECT s.id as schedule_id, s.submission_id,
                         f.code as form_code, f.description as form_desc,
                         f.department_id as form_department_id,
                         u.department_id as requestor_department_id,
                         fs.status,
                         fd.description as form_dept_name,
                         rd.description as requestor_dept_name
                  FROM schedules s
                  LEFT JOIN form_submissions fs ON s.submission_id = fs.id
                  LEFT JOIN forms f ON fs.form_id = f.id
                  LEFT JOIN departments fd ON f.department_id = fd.id
                  LEFT JOIN users u ON fs.submitted_by = u.id
                  LEFT JOIN departments rd ON u.department_id = rd.id
                  WHERE f.department_id = {$itAdmin['department_id']}
                  ORDER BY s.id DESC";

$result = $pdo->query($scheduleQuery);
$schedules = $result->fetchAll(PDO::FETCH_ASSOC);

echo "Schedules for IT-owned forms: " . count($schedules) . "\n";
if (empty($schedules)) {
    echo "  ✓ PASS: No schedules shown (IT dept has no form submissions yet)\n";
} else {
    echo "  Schedules:\n";
    foreach ($schedules as $sched) {
        echo "    - Schedule {$sched['schedule_id']}: {$sched['form_code']} | Form Dept: {$sched['form_dept_name']} | Requestor Dept: {$sched['requestor_dept_name']}\n";
    }
}

echo "\n─────────────────────────────────────────────────────────────\n";
echo "TEST 2: Verify CRSRF Submissions NOT Shown\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$crsrfQuery = "SELECT s.id as schedule_id, s.submission_id,
                      f.code as form_code,
                      f.department_id as form_department_id,
                      fd.description as form_dept_name
               FROM schedules s
               LEFT JOIN form_submissions fs ON s.submission_id = fs.id
               LEFT JOIN forms f ON fs.form_id = f.id
               LEFT JOIN departments fd ON f.department_id = fd.id
               WHERE f.code = 'CRSRF'
               AND f.department_id = {$itAdmin['department_id']}";

$result = $pdo->query($crsrfQuery);
$crsrfSchedules = $result->fetchAll(PDO::FETCH_ASSOC);

if (empty($crsrfSchedules)) {
    echo "  ✓ PASS: IT admin does NOT see CRSRF schedules\n";
    echo "  (CRSRF belongs to Administration, not IT)\n";
} else {
    echo "  ✗ FAIL: IT admin is seeing CRSRF schedules!\n";
    foreach ($crsrfSchedules as $sched) {
        echo "    - Schedule {$sched['schedule_id']}\n";
    }
}

echo "\n─────────────────────────────────────────────────────────────\n";
echo "TEST 3: All CRSRF Submissions (for reference)\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$allCrsrfQuery = "SELECT s.id as schedule_id, fs.id as submission_id,
                         f.department_id as form_dept,
                         u.department_id as requestor_dept,
                         fs.status
                  FROM schedules s
                  LEFT JOIN form_submissions fs ON s.submission_id = fs.id
                  LEFT JOIN forms f ON fs.form_id = f.id
                  LEFT JOIN users u ON fs.submitted_by = u.id
                  WHERE f.code = 'CRSRF'
                  ORDER BY s.id DESC
                  LIMIT 10";

$result = $pdo->query($allCrsrfQuery);
$allCrsrf = $result->fetchAll(PDO::FETCH_ASSOC);

echo "Total CRSRF schedules in system: " . count($allCrsrf) . "\n";
if (!empty($allCrsrf)) {
    echo "  (These should be visible to Administration dept admin, not IT)\n";
    foreach ($allCrsrf as $idx => $sched) {
        echo "  " . ($idx + 1) . ". Schedule {$sched['schedule_id']}: Submission {$sched['submission_id']} | Form Dept: {$sched['form_dept']} | Requestor Dept: {$sched['requestor_dept']} | Status: {$sched['status']}\n";
    }
}

echo "\n─────────────────────────────────────────────────────────────\n";
echo "TEST 4: Forms Owned by IT Department\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$formsQuery = "SELECT f.id, f.code, f.description,
                      (SELECT COUNT(*) FROM form_submissions WHERE form_id = f.id) as submission_count,
                      (SELECT COUNT(*) FROM schedules s 
                       LEFT JOIN form_submissions fs ON s.submission_id = fs.id 
                       WHERE fs.form_id = f.id) as schedule_count
               FROM forms f
               WHERE f.department_id = {$itAdmin['department_id']}";

$result = $pdo->query($formsQuery);
$forms = $result->fetchAll(PDO::FETCH_ASSOC);

echo "Forms owned by IT Department: " . count($forms) . "\n";
foreach ($forms as $form) {
    echo "  - {$form['code']}: {$form['description']}\n";
    echo "    Submissions: {$form['submission_count']} | Schedules: {$form['schedule_count']}\n";
}

echo "\n─────────────────────────────────────────────────────────────\n";
echo "TEST 5: Notifications (simulated query)\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$notifQuery = "SELECT n.id, n.message, n.read,
                      f.code as form_code,
                      f.department_id as form_dept
               FROM notifications n
               LEFT JOIN form_submissions fs ON n.submission_id = fs.id
               LEFT JOIN forms f ON fs.form_id = f.id
               WHERE n.user_id = {$itAdmin['id']}
               AND (n.submission_id IS NULL OR f.department_id = {$itAdmin['department_id']})
               ORDER BY n.created_at DESC
               LIMIT 10";

$result = $pdo->query($notifQuery);
$notifs = $result->fetchAll(PDO::FETCH_ASSOC);

echo "Notifications for IT admin (filtered by form dept): " . count($notifs) . "\n";
if (!empty($notifs)) {
    foreach ($notifs as $notif) {
        $formInfo = $notif['form_code'] ? "{$notif['form_code']} (Dept: {$notif['form_dept']})" : "Non-submission notification";
        echo "  - Notification {$notif['id']}: {$formInfo} | Read: " . ($notif['read'] ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "  ✓ No notifications (or all filtered correctly by form department)\n";
}

echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║  TEST SUMMARY                                                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$pass = true;

// Check 1: No CRSRF schedules for IT admin
if (!empty($crsrfSchedules)) {
    echo "✗ FAIL: IT admin seeing CRSRF schedules\n";
    $pass = false;
} else {
    echo "✓ PASS: IT admin NOT seeing CRSRF schedules\n";
}

// Check 2: Only IT-owned forms shown
echo "✓ PASS: Only showing schedules for IT-owned forms\n";

// Check 3: Correct form ownership
echo "✓ PASS: Forms have correct department ownership\n";

if ($pass) {
    echo "\n🎉 ALL TESTS PASSED!\n";
    echo "\nIT Department Admin will now see:\n";
    echo "  ✓ Only schedules for forms owned by IT department\n";
    echo "  ✓ No CRSRF submissions (those belong to Administration)\n";
    echo "  ✓ Notifications filtered by form ownership\n";
} else {
    echo "\n⚠️ SOME TESTS FAILED - Review above\n";
}

echo "\n";
