<?php
// Simple test without full CI4 bootstrap - directly test the query logic

$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=================================================================\n";
echo "NOTIFICATION FILTERING TEST (Direct Query)\n";
echo "=================================================================\n\n";

// Get department admin
$stmt = $pdo->query("
    SELECT u.id, u.full_name, u.user_type, u.department_id, d.description as dept_name
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

echo "Testing with: {$deptAdmin['full_name']} (ID: {$deptAdmin['id']})\n";
echo "Department: {$deptAdmin['dept_name']} (ID: {$deptAdmin['department_id']})\n\n";

// Test the FILTERED query (what the model should return)
echo "=== TESTING FILTERED QUERY (What model will return) ===\n";
$stmt = $pdo->prepare("
    SELECT n.*
    FROM notifications n
    LEFT JOIN form_submissions fs ON fs.id = n.submission_id
    LEFT JOIN users u ON u.id = fs.submitted_by
    WHERE n.user_id = ?
    AND (
        n.submission_id IS NULL
        OR u.department_id = ?
    )
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([$deptAdmin['id'], $deptAdmin['department_id']]);
$filteredNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Filtered query returned: " . count($filteredNotifications) . " notification(s)\n\n";

if (empty($filteredNotifications)) {
    echo "✓ No notifications returned!\n";
    echo "  This means the department admin will NOT see cross-department notifications.\n";
} else {
    foreach ($filteredNotifications as $notif) {
        echo "  - {$notif['title']} (Created: {$notif['created_at']})\n";
        
        if ($notif['submission_id']) {
            $stmt2 = $pdo->prepare("
                SELECT fs.id, u.full_name as submitter, u.department_id as submitter_dept, d.description as submitter_dept_name
                FROM form_submissions fs
                LEFT JOIN users u ON u.id = fs.submitted_by
                LEFT JOIN departments d ON d.id = u.department_id
                WHERE fs.id = ?
            ");
            $stmt2->execute([$notif['submission_id']]);
            $sub = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            if ($sub) {
                $match = ($sub['submitter_dept'] == $deptAdmin['department_id']) ? '✓' : '✗';
                echo "    {$match} Submitter: {$sub['submitter']} (Dept: {$sub['submitter_dept_name']})\n";
            }
        }
    }
}

// Compare with UNFILTERED query (old behavior)
echo "\n=== COMPARISON: UNFILTERED QUERY (Old behavior) ===\n";
$stmt = $pdo->prepare("
    SELECT n.*
    FROM notifications n
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([$deptAdmin['id']]);
$unfilteredNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Unfiltered query returned: " . count($unfilteredNotifications) . " notification(s)\n\n";

// Test unread count
echo "=== TESTING UNREAD COUNT QUERY ===\n";
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM notifications n
    LEFT JOIN form_submissions fs ON fs.id = n.submission_id
    LEFT JOIN users u ON u.id = fs.submitted_by
    WHERE n.user_id = ?
    AND n.read = 0
    AND (
        n.submission_id IS NULL
        OR u.department_id = ?
    )
");
$stmt->execute([$deptAdmin['id'], $deptAdmin['department_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Filtered unread count: {$result['count']}\n";

// Unfiltered count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND `read` = 0");
$stmt->execute([$deptAdmin['id']]);
$unfilteredResult = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Unfiltered unread count: {$unfilteredResult['count']}\n\n";

echo "=== SUMMARY ===\n";
$blockedCount = count($unfilteredNotifications) - count($filteredNotifications);
if ($blockedCount > 0) {
    echo "✓ SUCCESS: Filter blocked {$blockedCount} cross-department notification(s)!\n";
    echo "  Department admins will now only see notifications from their own department.\n";
} else {
    echo "ℹ All notifications are already from the correct department.\n";
}

echo "\n=================================================================\n";
echo "TEST COMPLETE\n";
echo "=================================================================\n";
