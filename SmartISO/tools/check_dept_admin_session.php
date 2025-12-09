<?php
// Test what a department admin session looks like

$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=================================================================\n";
echo "DEPARTMENT ADMIN SESSION CHECK\n";
echo "=================================================================\n\n";

// Get department admin
$stmt = $pdo->query("
    SELECT u.id, u.username, u.full_name, u.user_type, u.department_id, d.description as dept_name
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

echo "Department Admin Found:\n";
echo "  ID: {$deptAdmin['id']}\n";
echo "  Username: {$deptAdmin['username']}\n";
echo "  Full Name: {$deptAdmin['full_name']}\n";
echo "  User Type: {$deptAdmin['user_type']}\n";
echo "  Department ID: {$deptAdmin['department_id']}\n";
echo "  Department: {$deptAdmin['dept_name']}\n\n";

echo "Expected Session Values:\n";
echo "  session('user_id'): {$deptAdmin['id']}\n";
echo "  session('user_type'): {$deptAdmin['user_type']}\n";
echo "  session('department_id'): {$deptAdmin['department_id']}\n\n";

echo "Calendar() Method Logic Check:\n";
echo "  Will match: elseif (\$userType === 'department_admin')\n";
echo "  Should call: getDepartmentSchedules({$deptAdmin['department_id']})\n\n";

echo "=== RECOMMENDATION ===\n";
echo "1. Log in as: {$deptAdmin['username']}\n";
echo "2. Go to: /schedule/calendar\n";
echo "3. Check browser console for 'Calendar Debug Info'\n";
echo "4. Check logs for: 'Department Admin Calendar - User ID'\n";
echo "5. Verify if schedules from department {$deptAdmin['department_id']} only\n\n";

echo "To verify logs:\n";
echo "  tail -f writable/logs/log-" . date('Y-m-d') . ".php\n\n";

echo "=================================================================\n";
