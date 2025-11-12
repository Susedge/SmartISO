<?php
// Debug department submissions
$pdo = new PDO('mysql:host=localhost;dbname=smartiso', 'root', '');

// Simulate department admin user 9 with office 2, department 22
$userDeptId = 22;
$userOfficeId = 2;

echo "=== Department Submissions Debug ===\n\n";

// Get all users from the department
echo "1. Users in department {$userDeptId}:\n";
$stmt = $pdo->query("SELECT id, username, full_name, department_id, office_id FROM users WHERE department_id = {$userDeptId}");
$deptUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($deptUsers) . " users\n";
foreach ($deptUsers as $user) {
    echo "  - User ID: {$user['id']}, Username: {$user['username']}, Office: {$user['office_id']}\n";
}
$deptUserIds = array_column($deptUsers, 'id');
echo "\n";

// Get submissions from these users
echo "2. All submissions from department users:\n";
$userIdList = implode(',', $deptUserIds);
$stmt = $pdo->query("
    SELECT 
        fs.id, fs.form_id, fs.submitted_by, fs.status, fs.created_at,
        u.full_name, u.office_id as user_office_id,
        f.description as form_description
    FROM form_submissions fs
    LEFT JOIN users u ON u.id = fs.submitted_by
    LEFT JOIN forms f ON f.id = fs.form_id
    WHERE fs.submitted_by IN ({$userIdList})
    ORDER BY fs.created_at DESC
    LIMIT 10
");
$allSubmissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($allSubmissions) . " submissions\n";
foreach ($allSubmissions as $sub) {
    echo "  - ID: {$sub['id']}, Status: {$sub['status']}, User Office: {$sub['user_office_id']}, Form: {$sub['form_description']}\n";
}
echo "\n";

// Get submissions filtered by office
echo "3. Submissions filtered by office {$userOfficeId}:\n";
$stmt = $pdo->query("
    SELECT 
        fs.id, fs.form_id, fs.submitted_by, fs.status, fs.created_at,
        u.full_name, u.office_id as user_office_id,
        f.description as form_description
    FROM form_submissions fs
    LEFT JOIN users u ON u.id = fs.submitted_by
    LEFT JOIN forms f ON f.id = fs.form_id
    WHERE fs.submitted_by IN ({$userIdList})
    AND u.office_id = {$userOfficeId}
    ORDER BY fs.created_at DESC
    LIMIT 10
");
$officeSubmissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($officeSubmissions) . " submissions\n";
foreach ($officeSubmissions as $sub) {
    echo "  - ID: {$sub['id']}, Status: {$sub['status']}, User Office: {$sub['user_office_id']}, Form: {$sub['form_description']}\n";
}
echo "\n";

// Check stats
echo "4. Statistics:\n";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM form_submissions WHERE submitted_by IN ({$userIdList})");
echo "  Total (all dept): " . $stmt->fetch()['count'] . "\n";

$stmt = $pdo->query("SELECT COUNT(*) as count FROM form_submissions fs JOIN users u ON u.id = fs.submitted_by WHERE fs.submitted_by IN ({$userIdList}) AND u.office_id = {$userOfficeId}");
echo "  Total (office {$userOfficeId}): " . $stmt->fetch()['count'] . "\n";

$stmt = $pdo->query("SELECT COUNT(*) as count FROM form_submissions fs JOIN users u ON u.id = fs.submitted_by WHERE fs.submitted_by IN ({$userIdList}) AND u.office_id = {$userOfficeId} AND fs.status = 'submitted'");
echo "  Submitted (office {$userOfficeId}): " . $stmt->fetch()['count'] . "\n";

$stmt = $pdo->query("SELECT COUNT(*) as count FROM form_submissions fs JOIN users u ON u.id = fs.submitted_by WHERE fs.submitted_by IN ({$userIdList}) AND u.office_id = {$userOfficeId} AND fs.status IN ('approved', 'pending_service')");
echo "  Approved (office {$userOfficeId}): " . $stmt->fetch()['count'] . "\n";

$stmt = $pdo->query("SELECT COUNT(*) as count FROM form_submissions fs JOIN users u ON u.id = fs.submitted_by WHERE fs.submitted_by IN ({$userIdList}) AND u.office_id = {$userOfficeId} AND fs.status = 'completed'");
echo "  Completed (office {$userOfficeId}): " . $stmt->fetch()['count'] . "\n";

echo "\n=== Debug Complete ===\n";
