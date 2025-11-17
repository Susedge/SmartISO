<?php
/**
 * Simple diagnostic tool - Direct database queries
 * Check why "other device" shows different data
 */

// Database connection details
$host = 'localhost';
$database = 'smartiso';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

echo "========================================\n";
echo "OTHER DEVICE DIAGNOSTIC TOOL\n";
echo "========================================\n\n";

// Get user ID to check (default to service staff user ID 5)
$checkUserId = isset($argv[1]) ? (int)$argv[1] : 5;

echo "Checking for User ID: $checkUserId\n\n";

// 1. CHECK USER INFO
echo "=== 1. USER INFORMATION ===\n";
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$checkUserId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "✓ User found: {$user['full_name']}\n";
    echo "  - User Type: {$user['user_type']}\n";
    echo "  - Email: {$user['email']}\n";
    echo "  - Department ID: " . ($user['department_id'] ?? 'NULL') . "\n";
} else {
    echo "✗ User not found!\n";
    exit(1);
}
echo "\n";

// 2. CHECK SCHEDULES (CALENDAR DATA)
echo "=== 2. CALENDAR SCHEDULES ===\n";
$stmt = $pdo->prepare("
    SELECT 
        s.id,
        s.scheduled_date,
        s.scheduled_time,
        s.status as schedule_status,
        fs.status as submission_status,
        f.description as form_description,
        f.code as form_code
    FROM schedules s
    LEFT JOIN form_submissions fs ON fs.id = s.submission_id
    LEFT JOIN forms f ON f.id = fs.form_id
    WHERE s.assigned_staff_id = ?
    ORDER BY s.scheduled_date DESC
");
$stmt->execute([$checkUserId]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($schedules)) {
    echo "✗ NO SCHEDULES found!\n";
    echo "  → Calendar will be EMPTY for this user\n";
} else {
    echo "✓ Found " . count($schedules) . " schedules\n\n";
    
    $pendingCount = 0;
    $completedCount = 0;
    
    foreach ($schedules as $sched) {
        // Use submission_status if available, else schedule_status
        $displayStatus = $sched['submission_status'] ?? $sched['schedule_status'];
        
        echo "  Schedule ID: {$sched['id']}\n";
        echo "    Form: {$sched['form_description']} ({$sched['form_code']})\n";
        echo "    Date: {$sched['scheduled_date']} {$sched['scheduled_time']}\n";
        echo "    Schedule Status: {$sched['schedule_status']}\n";
        echo "    Submission Status: {$sched['submission_status']}\n";
        echo "    --> Display Status: $displayStatus\n";
        
        if ($displayStatus === 'completed') {
            $completedCount++;
            echo "    --> Should show as COMPLETED on calendar ✓\n";
        } else {
            $pendingCount++;
            echo "    --> Should show as PENDING on calendar ✓\n";
        }
        echo "\n";
    }
    
    echo "Summary:\n";
    echo "  - Pending events: $pendingCount\n";
    echo "  - Completed events: $completedCount\n";
    echo "  - Total events: " . count($schedules) . "\n";
}
echo "\n";

// 3. CHECK SERVICED BY ME PAGE
echo "=== 3. SERVICED BY ME DATA ===\n";
$stmt = $pdo->prepare("
    SELECT 
        fs.id,
        fs.status,
        fs.priority,
        fs.created_at,
        fs.updated_at,
        f.code as form_code,
        f.description as form_description,
        u.full_name as requestor_name,
        u.department_id as requestor_dept_id,
        s.priority_level,
        s.eta_days
    FROM form_submissions fs
    JOIN forms f ON f.id = fs.form_id
    JOIN users u ON u.id = fs.submitted_by
    LEFT JOIN schedules s ON s.submission_id = fs.id
    WHERE fs.service_staff_id = ?
    ORDER BY fs.updated_at DESC
");
$stmt->execute([$checkUserId]);
$servicedByMe = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($servicedByMe)) {
    echo "✗ NO SUBMISSIONS found for 'Serviced By Me'!\n";
    echo "  → Page will be EMPTY\n\n";
    
    // Check raw data
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM form_submissions WHERE service_staff_id = ?");
    $stmt->execute([$checkUserId]);
    $rawCount = $stmt->fetchColumn();
    
    echo "  Raw count from form_submissions: $rawCount\n";
    if ($rawCount > 0) {
        echo "  ⚠ Submissions exist but query returned empty!\n";
        echo "  Possible issue: Department filtering or join problem\n";
    }
} else {
    echo "✓ Found " . count($servicedByMe) . " submissions\n\n";
    
    foreach ($servicedByMe as $sub) {
        echo "  ID: {$sub['id']} | {$sub['form_description']}\n";
        echo "    Status: {$sub['status']}\n";
        echo "    Priority: {$sub['priority']}\n";
        echo "    Requestor: {$sub['requestor_name']} (Dept: {$sub['requestor_dept_id']})\n";
        echo "    Updated: {$sub['updated_at']}\n";
        echo "\n";
    }
}
echo "\n";

// 4. CHECK COMPLETED FORMS
echo "=== 4. COMPLETED FORMS DATA ===\n";
$stmt = $pdo->prepare("
    SELECT 
        fs.id,
        fs.status,
        f.description as form_description
    FROM form_submissions fs
    JOIN forms f ON f.id = fs.form_id
    WHERE fs.service_staff_id = ? AND fs.status = 'completed'
");
$stmt->execute([$checkUserId]);
$completedForms = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($completedForms)) {
    echo "✗ No completed forms found\n";
} else {
    echo "✓ Found " . count($completedForms) . " completed forms\n\n";
    foreach ($completedForms as $comp) {
        echo "  - ID: {$comp['id']} | {$comp['form_description']} | Status: {$comp['status']}\n";
    }
}
echo "\n";

// 5. DIAGNOSIS
echo "=== 5. DIAGNOSIS ===\n\n";

echo "What should appear:\n";
echo "  Calendar: Both pending AND completed events\n";
echo "  Serviced By Me: All submissions assigned to user\n";
echo "  Completed Forms: Only completed submissions\n\n";

echo "Database Reality:\n";
echo "  Calendar schedules: " . count($schedules) . " total";
if (!empty($schedules)) {
    echo " ($pendingCount pending, $completedCount completed)";
}
echo "\n";
echo "  Serviced By Me: " . count($servicedByMe) . " submissions\n";
echo "  Completed Forms: " . count($completedForms) . " submissions\n\n";

// ANALYSIS
$issues = [];

if (!empty($schedules) && $completedCount > 0) {
    echo "✓ Database has completed events\n";
    echo "  If calendar shows ONLY pending → BROWSER CACHE issue\n";
    echo "  Solution: Clear cache (Ctrl+Shift+Delete) + Hard refresh (Ctrl+F5)\n\n";
    $issues[] = "browser_cache";
}

if (empty($servicedByMe) && !empty($completedForms)) {
    echo "✗ 'Serviced By Me' is EMPTY but completed forms exist\n";
    echo "  Possible causes:\n";
    echo "    1. Different user logged in on other device\n";
    echo "    2. Session expired or corrupted\n";
    echo "    3. Department filtering issue\n\n";
    $issues[] = "serviced_by_me_empty";
}

if (empty($servicedByMe) && empty($completedForms) && empty($schedules)) {
    echo "✗ NO DATA at all for this user\n";
    echo "  → Verify user ID $checkUserId is correct\n";
    echo "  → Check if different user on other device\n\n";
    $issues[] = "no_data";
}

if (count($servicedByMe) !== count($schedules)) {
    echo "ℹ Note: Serviced By Me count (" . count($servicedByMe) . ") != Schedule count (" . count($schedules) . ")\n";
    echo "  This is normal if some submissions don't have schedules yet\n\n";
}

// RECOMMENDATIONS
echo "=== RECOMMENDATIONS ===\n\n";

if (in_array("browser_cache", $issues)) {
    echo "1. CLEAR BROWSER CACHE on other device:\n";
    echo "   - Press Ctrl+Shift+Delete\n";
    echo "   - Select 'Cached images and files'\n";
    echo "   - Click 'Clear data'\n";
    echo "   - Hard refresh: Ctrl+F5\n\n";
}

if (in_array("serviced_by_me_empty", $issues) || in_array("no_data", $issues)) {
    echo "2. VERIFY USER SESSION on other device:\n";
    echo "   - Is the same user logged in? (User ID: $checkUserId)\n";
    echo "   - Check session user_type = 'service_staff'\n";
    echo "   - Try logging out and back in\n\n";
}

echo "3. TEST IN INCOGNITO MODE first:\n";
echo "   - Ctrl+Shift+N (Chrome/Edge)\n";
echo "   - Login and check if data appears\n";
echo "   - If it works → confirms cache issue\n\n";

echo "========================================\n";
echo "To check different user:\n";
echo "  php check_other_device_simple.php <user_id>\n";
echo "========================================\n";
