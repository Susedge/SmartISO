<?php
/**
 * Diagnostic script - Simplified direct MySQL connection
 */

// Hardcoded for XAMPP default
$hostname = 'localhost';
$database = 'smartiso';  // SmartISO database name
$username = 'root';
$password = '';  // XAMPP default is empty password

echo "Connecting to database: $database@$hostname as $username\n";

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected successfully!\n\n";
} catch (PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\nPlease update the database credentials in this script.\n");
}

echo "=== PENDING SERVICE SUBMISSIONS DIAGNOSTIC ===\n\n";

// 1. Get all service staff users
echo "1. SERVICE STAFF USERS:\n";
echo str_repeat("-", 80) . "\n";
$stmt = $pdo->query("
    SELECT id, username, full_name, department_id, office_id
    FROM users
    WHERE user_type = 'service_staff' AND active = 1
    ORDER BY id
");
$serviceStaff = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($serviceStaff)) {
    echo "No service staff found in database.\n";
} else {
    foreach ($serviceStaff as $staff) {
        $dept = $staff['department_id'] ?? 'NULL';
        $office = $staff['office_id'] ?? 'NULL';
        echo "ID: {$staff['id']}, Name: {$staff['full_name']}, Dept: $dept, Office: $office\n";
    }
}

// 2. Get all submissions with status 'approved' or 'pending_service'
echo "\n2. SUBMISSIONS WITH STATUS 'approved' or 'pending_service':\n";
echo str_repeat("-", 80) . "\n";
$stmt = $pdo->query("
    SELECT 
        fs.id, fs.status, fs.service_staff_id,
        f.code as form_code,
        submitter.full_name as submitter_name,
        submitter.department_id as submitter_dept,
        staff.full_name as staff_name,
        staff.department_id as staff_dept
    FROM form_submissions fs
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    LEFT JOIN users staff ON staff.id = fs.service_staff_id
    WHERE fs.status IN ('approved', 'pending_service')
    ORDER BY fs.id DESC
    LIMIT 20
");
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($submissions)) {
    echo "No submissions found with status 'approved' or 'pending_service'\n";
} else {
    echo "Found " . count($submissions) . " submissions:\n\n";
    foreach ($submissions as $sub) {
        $staffId = $sub['service_staff_id'] ?? 'NULL';
        $staffName = $sub['staff_name'] ?? 'Unassigned';
        $staffDept = $sub['staff_dept'] ?? 'N/A';
        $submitterDept = $sub['submitter_dept'] ?? 'N/A';
        
        echo "Sub #{$sub['id']}: {$sub['form_code']} - {$sub['status']}\n";
        echo "  Submitter: {$sub['submitter_name']} (Dept: $submitterDept)\n";
        echo "  Service Staff: $staffName (ID: $staffId, Dept: $staffDept)\n";
        
        if ($staffId !== 'NULL' && $submitterDept !== $staffDept && $submitterDept !== 'N/A' && $staffDept !== 'N/A') {
            echo "  ⚠️  CROSS-DEPARTMENT! This would be filtered out with old logic!\n";
        }
        echo "\n";
    }
}

// 3. For each service staff, compare OLD vs NEW query
echo "\n3. COMPARING OLD VS NEW QUERY LOGIC FOR EACH SERVICE STAFF:\n";
echo str_repeat("-", 80) . "\n";

foreach ($serviceStaff as $staff) {
    $staffId = $staff['id'];
    $staffDept = $staff['department_id'];
    
    echo "\n--- {$staff['full_name']} (ID: $staffId, Dept: " . ($staffDept ?? 'NULL') . ") ---\n";
    
    // OLD QUERY (with department filter - INCORRECT)
    $sqlOld = "
        SELECT fs.id, f.code, submitter.full_name as submitter, submitter.department_id as sub_dept
        FROM form_submissions fs
        LEFT JOIN forms f ON f.id = fs.form_id
        LEFT JOIN users submitter ON submitter.id = fs.submitted_by
        WHERE fs.service_staff_id = $staffId
        AND fs.status IN ('approved', 'pending_service')
    ";
    if ($staffDept) {
        $sqlOld .= " AND submitter.department_id = $staffDept";
    }
    
    $stmtOld = $pdo->query($sqlOld);
    $oldResults = $stmtOld->fetchAll(PDO::FETCH_ASSOC);
    
    echo "OLD QUERY (with dept filter): " . count($oldResults) . " submissions\n";
    foreach ($oldResults as $r) {
        echo "  - Sub #{$r['id']}: {$r['code']} by {$r['submitter']} (Dept: {$r['sub_dept']})\n";
    }
    
    // NEW QUERY (without department filter - CORRECT)
    $sqlNew = "
        SELECT fs.id, f.code, submitter.full_name as submitter, submitter.department_id as sub_dept
        FROM form_submissions fs
        LEFT JOIN forms f ON f.id = fs.form_id
        LEFT JOIN users submitter ON submitter.id = fs.submitted_by
        WHERE fs.service_staff_id = $staffId
        AND fs.status IN ('approved', 'pending_service')
    ";
    
    $stmtNew = $pdo->query($sqlNew);
    $newResults = $stmtNew->fetchAll(PDO::FETCH_ASSOC);
    
    echo "NEW QUERY (no dept filter): " . count($newResults) . " submissions\n";
    foreach ($newResults as $r) {
        echo "  - Sub #{$r['id']}: {$r['code']} by {$r['submitter']} (Dept: {$r['sub_dept']})\n";
    }
    
    $diff = count($newResults) - count($oldResults);
    if ($diff > 0) {
        echo "✓ NEW QUERY SHOWS $diff MORE SUBMISSION(S)! The fix resolves the issue.\n";
    } elseif ($diff < 0) {
        echo "✗ NEW QUERY SHOWS FEWER! Something is wrong.\n";
    } else {
        echo "= Same results (no cross-department assignments)\n";
    }
}

echo "\n\n=== SUMMARY ===\n";
echo "The fix removes department filtering for service staff in the pending service view.\n";
echo "Service staff should see ALL submissions assigned to them, regardless of department.\n";
echo "This matches the calendar behavior where all assigned submissions are shown.\n";
