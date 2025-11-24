<?php
// CLI diagnostic: report department admins with their notification counts in the last N days
// Usage: php tools/check_dept_admin_notifications.php [days]

require __DIR__ . '/../vendor/autoload.php';

$days = isset($argv[1]) ? (int)$argv[1] : 30;
if ($days <= 0) $days = 30;

chdir(__DIR__ . '/..');
if (!class_exists('\Config\Database')) {
    echo "Please run this from the project root (needs CodeIgniter bootstrap available).\n";
    exit(1);
}

$db = \Config\Database::connect();

$threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

echo "Department Admin Notification Audit - last {$days} days (since {$threshold})\n\n";

$sql = "SELECT u.id, u.full_name, u.email, u.department_id, COUNT(n.id) as recent_notifications
        FROM users u
        LEFT JOIN notifications n ON n.user_id = u.id AND n.created_at >= ?
        WHERE u.user_type = 'department_admin' AND u.active = 1
        GROUP BY u.id, u.full_name, u.email, u.department_id
        ORDER BY recent_notifications ASC";

$results = $db->query($sql, [$threshold])->getResultArray();

foreach ($results as $row) {
    $deptId = $row['department_id'] ?? 'N/A';
    printf("ID: %4s | Name: %-30s | Email: %-30s | Dept: %4s | Notifs last %2d days: %3d\n",
        $row['id'], $row['full_name'] ?? '(no name)', $row['email'] ?? '(no email)', $deptId, $days, $row['recent_notifications']);
}

echo "\nIf some department admins show 0 notifications but should've received notifications,
check for (1) 'active' flag on user record, (2) submission form department matching, and (3) notification generation logs.\n";
