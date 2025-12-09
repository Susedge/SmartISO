<?php
// Check notification filtering issue for department admins

$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=================================================================\n";
echo "DEPARTMENT ADMIN NOTIFICATION ISSUE DIAGNOSTIC\n";
echo "=================================================================\n\n";

// Check notifications table structure
echo "=== NOTIFICATIONS TABLE STRUCTURE ===\n";
$stmt = $pdo->query('DESCRIBE notifications');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  {$row['Field']} - {$row['Type']}\n";
}

// Get department admin sample
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

// Check notifications for this department admin
echo "\n=== NOTIFICATIONS FOR THIS DEPARTMENT ADMIN ===\n";
$stmt = $pdo->prepare("
    SELECT n.id, n.title, n.created_at,
           fs.id as submission_id,
           submitter.full_name as submitter_name,
           submitter.department_id as submitter_dept,
           dept.description as submitter_dept_name
    FROM notifications n
    LEFT JOIN form_submissions fs ON fs.id = n.submission_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    LEFT JOIN departments dept ON dept.id = submitter.department_id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 10
");
$stmt->execute([$deptAdmin['id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "  Total notifications: " . count($notifications) . "\n\n";

$wrongDeptCount = 0;
foreach ($notifications as $notif) {
    $match = ($notif['submitter_dept'] == $deptAdmin['department_id']) ? '✓' : '✗';
    if ($match === '✗') $wrongDeptCount++;
    
    echo "  {$match} {$notif['title']}\n";
    echo "     Created: {$notif['created_at']}\n";
    if ($notif['submission_id']) {
        echo "     Submitter: {$notif['submitter_name']} - Dept: {$notif['submitter_dept_name']} (ID: {$notif['submitter_dept']})\n";
        echo "     Dept Admin's Dept: {$deptAdmin['dept_name']} (ID: {$deptAdmin['department_id']})\n";
    }
    echo "\n";
}

if ($wrongDeptCount > 0) {
    echo "\n⚠️  ISSUE CONFIRMED: {$wrongDeptCount} notification(s) from OTHER departments!\n";
    echo "    Department admin is seeing notifications for submissions outside their department.\n";
} else {
    echo "\n✓ No cross-department notifications found.\n";
}

// Check recent submissions and who got notified
echo "\n=== RECENT SUBMISSIONS & NOTIFICATION ROUTING ===\n";
$stmt = $pdo->query("
    SELECT fs.id as submission_id,
           f.code as form_code,
           submitter.full_name as submitter_name,
           submitter.department_id as submitter_dept,
           sdept.description as submitter_dept_name,
           COUNT(DISTINCT n.user_id) as notified_count,
           GROUP_CONCAT(DISTINCT CONCAT(nu.full_name, ' (', nu.user_type, ', Dept:', nu.department_id, ')') SEPARATOR '; ') as notified_users
    FROM form_submissions fs
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    LEFT JOIN departments sdept ON sdept.id = submitter.department_id
    LEFT JOIN notifications n ON n.submission_id = fs.id
    LEFT JOIN users nu ON nu.id = n.user_id
    WHERE fs.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY fs.id
    ORDER BY fs.created_at DESC
    LIMIT 5
");
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($submissions as $sub) {
    echo "\n  Submission ID: {$sub['submission_id']} - {$sub['form_code']}\n";
    echo "  Submitter: {$sub['submitter_name']} (Dept: {$sub['submitter_dept_name']})\n";
    echo "  Notified {$sub['notified_count']} users:\n";
    echo "    {$sub['notified_users']}\n";
}

echo "\n=================================================================\n";
echo "DIAGNOSIS COMPLETE\n";
echo "=================================================================\n";
