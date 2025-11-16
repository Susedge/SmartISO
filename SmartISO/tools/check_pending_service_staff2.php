<?php
/**
 * Diagnostic script to check pending service submissions and verify service staff assignments
 */

// Direct database connection
$envFile = __DIR__ . '/../env';
if (!file_exists($envFile)) {
    die("Error: env file not found\n");
}

$envContent = file_get_contents($envFile);
preg_match('/database\.default\.hostname\s*=\s*(.+)/', $envContent, $host);
preg_match('/database\.default\.database\s*=\s*(.+)/', $envContent, $dbname);
preg_match('/database\.default\.username\s*=\s*(.+)/', $envContent, $user);
preg_match('/database\.default\.password\s*=\s*(.+)/', $envContent, $pass);

$hostname = trim($host[1] ?? 'localhost');
$database = trim($dbname[1] ?? 'smartiso_db');
$username = trim($user[1] ?? 'root');
$password = trim($pass[1] ?? '');

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

echo "=== PENDING SERVICE SUBMISSIONS DIAGNOSTIC ===\n\n";

// Get all service staff users
echo "1. SERVICE STAFF USERS:\n";
echo str_repeat("-", 80) . "\n";
$stmt = $pdo->query("
    SELECT id, username, full_name, email, department_id, office_id, user_type
    FROM users
    WHERE user_type = 'service_staff' AND active = 1
");
$serviceStaff = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($serviceStaff as $staff) {
    echo "ID: {$staff['id']}, Name: {$staff['full_name']}, Dept: {$staff['department_id']}, Office: {$staff['office_id']}\n";
}

echo "\n2. SUBMISSIONS WITH STATUS 'approved' or 'pending_service':\n";
echo str_repeat("-", 80) . "\n";
$stmt = $pdo->query("
    SELECT 
        fs.id, fs.form_id, fs.status, fs.service_staff_id, fs.submitted_by,
        f.code as form_code, f.description as form_description,
        submitter.full_name as submitter_name, submitter.department_id as submitter_dept,
        staff.full_name as staff_name, staff.department_id as staff_dept
    FROM form_submissions fs
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    LEFT JOIN users staff ON staff.id = fs.service_staff_id
    WHERE fs.status IN ('approved', 'pending_service')
    ORDER BY fs.id DESC
");
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($submissions)) {
    echo "No submissions found with status 'approved' or 'pending_service'\n";
} else {
    foreach ($submissions as $sub) {
        echo "\nSubmission ID: {$sub['id']}\n";
        echo "  Form: {$sub['form_code']} - {$sub['form_description']}\n";
        echo "  Status: {$sub['status']}\n";
        echo "  Submitter: {$sub['submitter_name']} (Dept: {$sub['submitter_dept']})\n";
        echo "  Service Staff ID: " . ($sub['service_staff_id'] ?? 'NULL') . "\n";
        if ($sub['service_staff_id']) {
            echo "  Service Staff: {$sub['staff_name']} (Dept: {$sub['staff_dept']})\n";
            echo "  CROSS-DEPT? " . ($sub['submitter_dept'] != $sub['staff_dept'] ? "YES - This might be filtered out!" : "No") . "\n";
        }
    }
}

echo "\n\n3. CHECKING PENDING SERVICE QUERY RESULTS BY SERVICE STAFF:\n";
echo str_repeat("-", 80) . "\n";

foreach ($serviceStaff as $staff) {
    echo "\n--- Service Staff: {$staff['full_name']} (ID: {$staff['id']}, Dept: {$staff['department_id']}) ---\n";
    
    // OLD QUERY (with department filter)
    echo "\nOLD QUERY (with dept filter):\n";
    $sql = "
        SELECT fs.id, fs.status, f.code, submitter.full_name as submitter, submitter.department_id as submitter_dept
        FROM form_submissions fs
        LEFT JOIN forms f ON f.id = fs.form_id
        LEFT JOIN users submitter ON submitter.id = fs.submitted_by
        WHERE fs.service_staff_id = :staff_id
        AND fs.status IN ('approved', 'pending_service')
    ";
    
    // Add old department filter (incorrect for service staff)
    if ($staff['department_id']) {
        $sql .= " AND submitter.department_id = :dept_id";
    }
    
    $stmtOld = $pdo->prepare($sql);
    $stmtOld->bindValue(':staff_id', $staff['id']);
    if ($staff['department_id']) {
        $stmtOld->bindValue(':dept_id', $staff['department_id']);
    }
    $stmtOld->execute();
    $oldResults = $stmtOld->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  Found: " . count($oldResults) . " submissions\n";
    foreach ($oldResults as $r) {
        echo "    - Sub #{$r['id']}: {$r['code']} by {$r['submitter']} (Dept: {$r['submitter_dept']})\n";
    }
    
    // NEW QUERY (without department filter for service staff)
    echo "\nNEW QUERY (without dept filter - CORRECT):\n";
    $stmtNew = $pdo->prepare("
        SELECT fs.id, fs.status, f.code, submitter.full_name as submitter, submitter.department_id as submitter_dept
        FROM form_submissions fs
        LEFT JOIN forms f ON f.id = fs.form_id
        LEFT JOIN users submitter ON submitter.id = fs.submitted_by
        WHERE fs.service_staff_id = :staff_id
        AND fs.status IN ('approved', 'pending_service')
    ");
    $stmtNew->bindValue(':staff_id', $staff['id']);
    $stmtNew->execute();
    $newResults = $stmtNew->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  Found: " . count($newResults) . " submissions\n";
    foreach ($newResults as $r) {
        echo "    - Sub #{$r['id']}: {$r['code']} by {$r['submitter']} (Dept: {$r['submitter_dept']})\n";
    }
    
    $diff = count($newResults) - count($oldResults);
    if ($diff > 0) {
        echo "  ✓ NEW QUERY SHOWS {$diff} MORE SUBMISSION(S) - This is the fix!\n";
    } elseif ($diff < 0) {
        echo "  ✗ NEW QUERY SHOWS FEWER - Something is wrong!\n";
    } else {
        echo "  = Same results (no cross-department assignments for this staff)\n";
    }
}

echo "\n\n4. CHECKING SCHEDULE CALENDAR QUERY:\n";
echo str_repeat("-", 80) . "\n";
foreach ($serviceStaff as $staff) {
    echo "\nService Staff: {$staff['full_name']} (ID: {$staff['id']})\n";
    
    // Check what calendar would show (submissions without schedules)
    $stmtCal = $pdo->prepare("
        SELECT fs.id, fs.status, f.code, submitter.full_name as submitter
        FROM form_submissions fs
        LEFT JOIN forms f ON f.id = fs.form_id
        LEFT JOIN users submitter ON submitter.id = fs.submitted_by
        WHERE fs.service_staff_id = :staff_id
        AND NOT EXISTS (SELECT 1 FROM schedules s WHERE s.submission_id = fs.id)
        AND fs.status IN ('approved', 'pending_service', 'completed')
    ");
    $stmtCal->bindValue(':staff_id', $staff['id']);
    $stmtCal->execute();
    $calendarResults = $stmtCal->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  Calendar shows: " . count($calendarResults) . " submissions (without schedules)\n";
    foreach ($calendarResults as $r) {
        echo "    - Sub #{$r['id']}: {$r['code']} ({$r['status']}) by {$r['submitter']}\n";
    }
}

echo "\n\n=== DIAGNOSTIC COMPLETE ===\n";
echo "If the NEW QUERY shows more results than OLD QUERY, the fix is working correctly.\n";
echo "Service staff should see ALL submissions assigned to them, regardless of requestor department.\n";
