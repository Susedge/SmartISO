<?php
$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== CHECKING IT DEPARTMENT ADMIN SESSION DATA ===\n\n";

// Get IT dept admin
$query = "SELECT id, username, full_name, user_type, department_id FROM users WHERE username = 'dept_admin_it'";
$result = $pdo->query($query);
$admin = $result->fetch(PDO::FETCH_ASSOC);

echo "IT Department Admin from database:\n";
echo "  ID: " . $admin['id'] . "\n";
echo "  Username: " . $admin['username'] . "\n";
echo "  Full Name: " . $admin['full_name'] . "\n";
echo "  User Type: " . $admin['user_type'] . "\n";
echo "  Department ID: " . $admin['department_id'] . "\n\n";

echo "Expected session values:\n";
echo "  session('user_id') should be: " . $admin['id'] . "\n";
echo "  session('user_type') should be: '" . $admin['user_type'] . "'\n";
echo "  session('department_id') should be: " . $admin['department_id'] . "\n\n";

echo "⚠️ IMPORTANT: The user_type must be EXACTLY 'department_admin' (case-sensitive)\n";
echo "If it's 'Department_Admin' or 'departmentAdmin', the elseif won't match!\n\n";

// Check for variations in database
echo "Checking for user_type variations in database:\n";
$query2 = "SELECT DISTINCT user_type FROM users ORDER BY user_type";
$result2 = $pdo->query($query2);
$types = $result2->fetchAll(PDO::FETCH_COLUMN);

foreach ($types as $type) {
    echo "  - '" . $type . "'";
    if ($type === 'department_admin') {
        echo " ← IT admin uses this\n";
    } else {
        echo "\n";
    }
}

echo "\n=== DEBUGGING CALENDAR ACCESS ===\n\n";
echo "To debug, check the logs at writable/logs/log-" . date('Y-m-d') . ".php\n";
echo "Look for these entries:\n";
echo "  1. 'Calendar accessed - User Type: XXX, User ID: YYY'\n";
echo "  2. 'Department Admin Calendar START' (should appear for dept_admin_it)\n";
echo "  3. 'getDepartmentSchedules returned: N schedule(s)'\n\n";

echo "If you don't see 'Department Admin Calendar START', then:\n";
echo "  - The user_type in session doesn't match 'department_admin'\n";
echo "  - OR the elseif is falling through to the else block\n";
