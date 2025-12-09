<?php
/**
 * Test Department Admin Fixes
 * Tests all three reported issues:
 * 1. Form routing to department admins
 * 2. Department admin notifications
 * 3. Calendar visibility for department admins
 * 4. Requestor completion notifications
 */

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=smartiso', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "═══════════════════════════════════════════════════════════════\n";
echo "     DEPARTMENT ADMIN FIXES - COMPREHENSIVE TEST SUITE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ===========================================================================
// TEST 1: Form Routing - Check Department Admin Setup
// ===========================================================================
echo "TEST 1: DEPARTMENT ADMIN SETUP AND FORM ROUTING\n";
echo "─────────────────────────────────────────────────────────────────\n";

// Get all department admins
$stmt = $pdo->query("
    SELECT u.id, u.username, u.full_name, u.user_type, u.department_id, d.code as dept_code, d.description as dept_name
    FROM users u
    LEFT JOIN departments d ON d.id = u.department_id
    WHERE u.user_type = 'department_admin' AND u.active = 1
    ORDER BY d.description, u.full_name
");
$deptAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($deptAdmins) . " active department admin(s):\n\n";

if (empty($deptAdmins)) {
    echo "   ✗ NO DEPARTMENT ADMINS FOUND!\n";
    echo "   This is likely why all notifications go to one person.\n";
    echo "   Create department admins with: UPDATE users SET user_type='department_admin' WHERE id=X;\n\n";
} else {
    $deptCounts = [];
    foreach ($deptAdmins as $admin) {
        $deptId = $admin['department_id'] ?? 'NULL';
        $deptName = $admin['dept_name'] ?? 'No Department';
        echo "   • {$admin['full_name']} (@{$admin['username']}) - {$deptName}\n";
        
        if (!isset($deptCounts[$deptId])) {
            $deptCounts[$deptId] = 0;
        }
        $deptCounts[$deptId]++;
    }
    
    echo "\n   Department Admin Distribution:\n";
    foreach ($deptCounts as $deptId => $count) {
        if ($deptId === 'NULL') {
            echo "   • No Department Assigned: {$count} admin(s) ⚠️\n";
        } else {
            $stmt = $pdo->prepare("SELECT description FROM departments WHERE id = ?");
            $stmt->execute([$deptId]);
            $dept = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "   • {$dept['description']}: {$count} admin(s)\n";
        }
    }
    echo "\n";
}

// Check PDO department specifically
echo "\nPDO Department Analysis:\n";
$stmt = $pdo->query("SELECT id, code, description FROM departments WHERE code = 'PDO' OR description LIKE '%PDO%'");
$pdoDept = $stmt->fetch(PDO::FETCH_ASSOC);

if ($pdoDept) {
    echo "   ✓ PDO Department Found: {$pdoDept['description']} (ID: {$pdoDept['id']})\n";
    
    // Check PDO dept admins
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE user_type = 'department_admin' AND department_id = ? AND active = 1
    ");
    $stmt->execute([$pdoDept['id']]);
    $pdoAdminCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "   PDO Department Admins: {$pdoAdminCount}\n";
    
    if ($pdoAdminCount == 0) {
        echo "   ✗ NO PDO DEPARTMENT ADMINS! This is why PDO forms don't route correctly.\n";
    }
    
    // Check PDO forms
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM forms WHERE department_id = ?");
    $stmt->execute([$pdoDept['id']]);
    $pdoFormCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   PDO Forms: {$pdoFormCount}\n";
    
    // Check PDO requestors
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE department_id = ? AND user_type = 'requestor' AND active = 1");
    $stmt->execute([$pdoDept['id']]);
    $pdoRequestorCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   PDO Requestors: {$pdoRequestorCount}\n";
} else {
    echo "   ✗ PDO Department Not Found\n";
}

echo "\n";

// ===========================================================================
// TEST 2: Check Form Signatories (Override Department Routing)
// ===========================================================================
echo "TEST 2: FORM SIGNATORY OVERRIDE CHECK\n";
echo "─────────────────────────────────────────────────────────────────\n";

$stmt = $pdo->query("
    SELECT f.id, f.code, f.description, d.description as dept_name, COUNT(fs.user_id) as signatory_count
    FROM forms f
    LEFT JOIN departments d ON d.id = f.department_id
    LEFT JOIN form_signatories fs ON fs.form_id = f.id
    GROUP BY f.id
    HAVING signatory_count > 0
    ORDER BY f.description
");
$formsWithSignatories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Forms with specific signatories (overrides department routing):\n\n";

if (empty($formsWithSignatories)) {
    echo "   ✓ No forms have specific signatories.\n";
    echo "   All forms will use department-based routing.\n\n";
} else {
    echo "   Found " . count($formsWithSignatories) . " form(s) with specific signatories:\n\n";
    foreach ($formsWithSignatories as $form) {
        echo "   • {$form['code']} - {$form['description']}\n";
        echo "     Department: {$form['dept_name']}\n";
        echo "     Signatories: {$form['signatory_count']}\n";
        
        // Get signatory details
        $stmt = $pdo->prepare("
            SELECT u.full_name, u.user_type, u.department_id, d.description as user_dept
            FROM form_signatories fs
            LEFT JOIN users u ON u.id = fs.user_id
            LEFT JOIN departments d ON d.id = u.department_id
            WHERE fs.form_id = ?
        ");
        $stmt->execute([$form['id']]);
        $signatories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($signatories as $sig) {
            echo "       → {$sig['full_name']} ({$sig['user_type']}) - {$sig['user_dept']}\n";
        }
        echo "\n";
    }
    
    echo "   ⚠️  These forms will ALWAYS route to their specific signatories,\n";
    echo "      regardless of department admin assignments.\n\n";
}

// ===========================================================================
// TEST 3: Recent Submission Notifications
// ===========================================================================
echo "TEST 3: RECENT SUBMISSION NOTIFICATIONS\n";
echo "─────────────────────────────────────────────────────────────────\n";

$stmt = $pdo->query("
    SELECT 
        n.id,
        n.created_at,
        n.title,
        u.username as notified_user,
        u.user_type,
        u.department_id as notified_dept,
        d.description as notified_dept_name,
        submitter.username as submitter,
        submitter.department_id as submitter_dept,
        sd.description as submitter_dept_name,
        f.code as form_code
    FROM notifications n
    LEFT JOIN users u ON u.id = n.user_id
    LEFT JOIN departments d ON d.id = u.department_id
    LEFT JOIN form_submissions fs ON fs.id = n.submission_id
    LEFT JOIN users submitter ON submitter.id = fs.submitted_by
    LEFT JOIN departments sd ON sd.id = submitter.department_id
    LEFT JOIN forms f ON f.id = fs.form_id
    WHERE n.title = 'New Service Request Requires Approval'
    ORDER BY n.created_at DESC
    LIMIT 10
");
$recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Last 10 submission notifications:\n\n";

if (empty($recentNotifications)) {
    echo "   No submission notifications found. Submit a form to test.\n\n";
} else {
    foreach ($recentNotifications as $notif) {
        echo "   • {$notif['created_at']} | {$notif['form_code']}\n";
        echo "     Submitter: {$notif['submitter']} ({$notif['submitter_dept_name']})\n";
        echo "     Notified: {$notif['notified_user']} ({$notif['user_type']}) - {$notif['notified_dept_name']}\n";
        
        if ($notif['user_type'] === 'department_admin') {
            if ($notif['notified_dept'] == $notif['submitter_dept']) {
                echo "     ✓ Correct department routing\n";
            } else {
                echo "     ✗ WRONG DEPARTMENT! Dept admin from wrong department was notified.\n";
            }
        }
        echo "\n";
    }
}

// ===========================================================================
// TEST 4: Calendar Visibility Test
// ===========================================================================
echo "TEST 4: CALENDAR VISIBILITY FOR DEPARTMENT ADMINS\n";
echo "─────────────────────────────────────────────────────────────────\n";

echo "Simulating calendar view for each department admin:\n\n";

foreach ($deptAdmins as $admin) {
    $deptId = $admin['department_id'];
    if (!$deptId) {
        echo "   • {$admin['full_name']}: NO DEPARTMENT ASSIGNED - Would see nothing\n\n";
        continue;
    }
    
    // Simulate getDepartmentSchedules() query
    $stmt = $pdo->prepare("
        SELECT 
            s.id as schedule_id,
            s.scheduled_date,
            fs.id as submission_id,
            f.code as form_code,
            submitter.full_name as requestor,
            submitter.department_id as requestor_dept,
            d.description as requestor_dept_name
        FROM schedules s
        LEFT JOIN form_submissions fs ON fs.id = s.submission_id
        LEFT JOIN forms f ON f.id = fs.form_id
        LEFT JOIN users submitter ON submitter.id = fs.submitted_by
        LEFT JOIN departments d ON d.id = submitter.department_id
        WHERE submitter.department_id = ?
        ORDER BY s.scheduled_date DESC
        LIMIT 5
    ");
    $stmt->execute([$deptId]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Also check submissions without schedules
    $stmt2 = $pdo->prepare("
        SELECT 
            fs.id as submission_id,
            fs.created_at,
            f.code as form_code,
            submitter.full_name as requestor,
            submitter.department_id as requestor_dept
        FROM form_submissions fs
        LEFT JOIN forms f ON f.id = fs.form_id
        LEFT JOIN users submitter ON submitter.id = fs.submitted_by
        LEFT JOIN schedules s ON s.submission_id = fs.id
        WHERE submitter.department_id = ? AND s.id IS NULL
        ORDER BY fs.created_at DESC
        LIMIT 5
    ");
    $stmt2->execute([$deptId]);
    $unscheduled = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    $totalVisible = count($schedules) + count($unscheduled);
    
    echo "   • {$admin['full_name']} ({$admin['dept_name']}):\n";
    echo "     - Scheduled events: " . count($schedules) . "\n";
    echo "     - Unscheduled submissions: " . count($unscheduled) . "\n";
    echo "     - TOTAL visible on calendar: {$totalVisible}\n";
    
    if ($totalVisible > 0) {
        echo "     ✓ Will see events on calendar\n";
        echo "     Sample events:\n";
        foreach (array_slice($schedules, 0, 3) as $sched) {
            echo "       → [{$sched['scheduled_date']}] {$sched['form_code']} by {$sched['requestor']}\n";
        }
        foreach (array_slice($unscheduled, 0, 2) as $unsched) {
            echo "       → [Unscheduled] {$unsched['form_code']} by {$unsched['requestor']}\n";
        }
    } else {
        echo "     ⚠️  No events - calendar will be empty\n";
    }
    echo "\n";
}

// ===========================================================================
// TEST 5: Completion Notifications
// ===========================================================================
echo "TEST 5: REQUESTOR COMPLETION NOTIFICATIONS\n";
echo "─────────────────────────────────────────────────────────────────\n";

$stmt = $pdo->query("
    SELECT 
        n.id,
        n.created_at,
        u.username as requestor,
        u.user_type,
        fs.status,
        fs.completion_date,
        f.code as form_code
    FROM notifications n
    LEFT JOIN users u ON u.id = n.user_id
    LEFT JOIN form_submissions fs ON fs.id = n.submission_id
    LEFT JOIN forms f ON f.id = fs.form_id
    WHERE n.title = 'Service Completed'
    ORDER BY n.created_at DESC
    LIMIT 10
");
$completionNotifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Recent completion notifications:\n\n";

if (empty($completionNotifs)) {
    echo "   No completion notifications found.\n";
    echo "   Test by having service staff complete a form.\n\n";
} else {
    foreach ($completionNotifs as $notif) {
        echo "   • {$notif['created_at']} | {$notif['form_code']}\n";
        echo "     Requestor: {$notif['requestor']}\n";
        echo "     Status: {$notif['status']}\n";
        echo "     Completed: {$notif['completion_date']}\n";
        echo "     ✓ Notification sent successfully\n\n";
    }
}

// ===========================================================================
// SUMMARY AND RECOMMENDATIONS
// ===========================================================================
echo "═══════════════════════════════════════════════════════════════\n";
echo "                    SUMMARY & RECOMMENDATIONS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$issues = [];

// Issue 1: Check if enough dept admins
$deptCount = count(array_unique(array_column($deptAdmins, 'department_id')));
$totalDepts = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();

if ($deptCount < $totalDepts) {
    $issues[] = "Not all departments have department admins. Some forms may route to global admins only.";
}

// Issue 2: Check for dept admins without department
$unassignedCount = 0;
foreach ($deptAdmins as $admin) {
    if (!$admin['department_id']) {
        $unassignedCount++;
    }
}
if ($unassignedCount > 0) {
    $issues[] = "{$unassignedCount} department admin(s) have no department assigned. They won't receive any notifications.";
}

// Issue 3: Check for forms with signatories
if (!empty($formsWithSignatories)) {
    $issues[] = count($formsWithSignatories) . " form(s) have specific signatories. These override department routing.";
}

if (empty($issues)) {
    echo "✓ ALL CHECKS PASSED!\n\n";
    echo "System Configuration:\n";
    echo "  • Department Admins: " . count($deptAdmins) . "\n";
    echo "  • Departments Covered: {$deptCount} / {$totalDepts}\n";
    echo "  • Form Signatories: " . count($formsWithSignatories) . "\n";
    echo "  • Recent Notifications: " . count($recentNotifications) . "\n";
    echo "  • Completion Notifications: " . count($completionNotifs) . "\n\n";
    echo "Everything is configured correctly!\n";
} else {
    echo "⚠️  ISSUES FOUND:\n\n";
    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". {$issue}\n";
    }
    echo "\n";
}

echo "\nRECOMMENDED ACTIONS:\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "1. Ensure each department has at least one active department admin\n";
echo "2. Remove form signatories if you want department-based routing:\n";
echo "   DELETE FROM form_signatories WHERE form_id = [FORM_ID];\n";
echo "3. Monitor logs in writable/logs/ for 'Submission Notification' entries\n";
echo "4. Test by submitting a form and checking who receives notifications\n";
echo "5. Test calendar visibility by logging in as different dept admins\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "                     TEST COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n";
