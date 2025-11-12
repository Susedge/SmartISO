<?php
// Comprehensive check of all submissions and users
$pdo = new PDO('mysql:host=localhost;dbname=smartiso', 'root', '');

echo "=== Comprehensive Database Check ===\n\n";

// Check current session user
echo "1. Department Admin User Info:\n";
$stmt = $pdo->query("SELECT id, username, full_name, user_type, department_id, office_id FROM users WHERE username = 'dept_admin_it'");
$deptAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
if ($deptAdmin) {
    echo "  User ID: {$deptAdmin['id']}\n";
    echo "  Username: {$deptAdmin['username']}\n";
    echo "  Type: {$deptAdmin['user_type']}\n";
    echo "  Department ID: {$deptAdmin['department_id']}\n";
    echo "  Office ID: {$deptAdmin['office_id']}\n\n";
}

// Check ALL submissions with full details
echo "2. ALL Form Submissions (complete list):\n";
$stmt = $pdo->query("
    SELECT 
        fs.id, fs.form_id, fs.submitted_by, fs.status, fs.priority, fs.created_at,
        u.id as user_id, u.username, u.full_name, u.department_id, u.office_id,
        d.description as department_name,
        o.description as office_name,
        f.code as form_code
    FROM form_submissions fs
    LEFT JOIN users u ON u.id = fs.submitted_by
    LEFT JOIN departments d ON d.id = u.department_id
    LEFT JOIN offices o ON o.id = u.office_id
    LEFT JOIN forms f ON f.id = fs.form_id
    ORDER BY fs.id ASC
");

$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($submissions) . " total submissions\n\n";

$submissionsByDept = [];
foreach ($submissions as $sub) {
    $deptId = $sub['department_id'] ?? 'NULL';
    if (!isset($submissionsByDept[$deptId])) {
        $submissionsByDept[$deptId] = [];
    }
    $submissionsByDept[$deptId][] = $sub;
    
    echo "ID: {$sub['id']} | User: {$sub['username']} (ID:{$sub['user_id']}) | Dept: {$sub['department_name']} (ID:{$sub['department_id']}) | Office: {$sub['office_name']} (ID:{$sub['office_id']}) | Status: {$sub['status']} | Priority: {$sub['priority']}\n";
}

echo "\n3. Submissions grouped by department:\n";
foreach ($submissionsByDept as $deptId => $subs) {
    $deptName = $subs[0]['department_name'] ?? 'N/A';
    echo "  Department {$deptId} ({$deptName}): " . count($subs) . " submissions\n";
}

// Check schedules table for priority
echo "\n4. Schedules with priority levels:\n";
$stmt = $pdo->query("
    SELECT s.id, s.submission_id, s.priority_level, s.eta_days, 
           fs.priority as submission_priority
    FROM schedules s
    LEFT JOIN form_submissions fs ON fs.id = s.submission_id
    ORDER BY s.submission_id
");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($schedules) . " schedules\n";
foreach ($schedules as $sch) {
    echo "  Submission ID: {$sch['submission_id']} | Schedule Priority: {$sch['priority_level']} | Submission Priority: {$sch['submission_priority']} | ETA: {$sch['eta_days']}d\n";
}

echo "\n=== Debug Complete ===\n";
