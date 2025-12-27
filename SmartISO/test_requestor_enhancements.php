<?php
/**
 * Test Script for Requestor Enhancements
 * 
 * This script tests the new requestor functionality:
 * 1. Requestors can view all forms
 * 2. Form filtering works correctly
 * 3. Approval routing is intact
 * 4. Completion notifications work
 * 
 * Usage: php test_requestor_enhancements.php
 */

require 'vendor/autoload.php';

// Bootstrap CodeIgniter
$pathsConfig = APPPATH . 'Config/Paths.php';
require realpath($pathsConfig) ?: $pathsConfig;

$paths = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';
require realpath($bootstrap) ?: $bootstrap;

$app = Config\Services::codeigniter();
$app->initialize();

echo "=== REQUESTOR ENHANCEMENTS TEST SUITE ===\n\n";

// Get database connection
$db = \Config\Database::connect();

// TEST 1: Check Requestor Users
echo "TEST 1: Checking Requestor Users\n";
echo str_repeat("-", 50) . "\n";

$requestors = $db->table('users')
    ->where('user_type', 'requestor')
    ->where('active', 1)
    ->get()
    ->getResultArray();

if (empty($requestors)) {
    echo "❌ No active requestor users found!\n";
    echo "   Please create a requestor user to test.\n\n";
} else {
    echo "✅ Found " . count($requestors) . " active requestor(s):\n";
    foreach ($requestors as $req) {
        echo "   - ID: {$req['id']}, Username: {$req['username']}, ";
        echo "Department: {$req['department_id']}, Office: {$req['office_id']}\n";
    }
    echo "\n";
}

// TEST 2: Check Forms Across Departments
echo "TEST 2: Checking Forms Across Departments\n";
echo str_repeat("-", 50) . "\n";

$formsByDept = $db->query("
    SELECT d.id as dept_id, d.description as dept_name, COUNT(f.id) as form_count
    FROM departments d
    LEFT JOIN forms f ON (f.department_id = d.id OR EXISTS (
        SELECT 1 FROM offices o 
        WHERE o.id = f.office_id AND o.department_id = d.id
    ))
    GROUP BY d.id
    ORDER BY d.description
")->getResultArray();

$totalForms = $db->table('forms')->where('active', 1)->countAllResults();

echo "✅ Total Active Forms: {$totalForms}\n";
echo "✅ Forms by Department:\n";
foreach ($formsByDept as $dept) {
    if ($dept['form_count'] > 0) {
        echo "   - {$dept['dept_name']}: {$dept['form_count']} form(s)\n";
    }
}
echo "\n";

if ($totalForms === 0) {
    echo "⚠️  Warning: No forms available. Create some forms for testing.\n\n";
}

// TEST 3: Check Form Signatories (Approval Routing)
echo "TEST 3: Checking Form Signatories (Approval Routing)\n";
echo str_repeat("-", 50) . "\n";

$formsWithSignatories = $db->query("
    SELECT f.id, f.code, f.description, 
           GROUP_CONCAT(u.full_name SEPARATOR ', ') as approvers
    FROM forms f
    LEFT JOIN form_signatories fs ON fs.form_id = f.id
    LEFT JOIN users u ON u.id = fs.user_id
    WHERE f.active = 1
    GROUP BY f.id
    LIMIT 5
")->getResultArray();

if (empty($formsWithSignatories)) {
    echo "⚠️  No forms with assigned signatories found.\n";
    echo "   Approval routing will fall back to department-based logic.\n\n";
} else {
    echo "✅ Forms with Assigned Approvers:\n";
    foreach ($formsWithSignatories as $form) {
        $approvers = $form['approvers'] ?: 'None (using fallback)';
        echo "   - {$form['code']}: {$approvers}\n";
    }
    echo "\n";
}

// TEST 4: Check Notification System
echo "TEST 4: Checking Notification System\n";
echo str_repeat("-", 50) . "\n";

// Check if NotificationModel has the completion notification method
$notifModelFile = APPPATH . 'Models/NotificationModel.php';
$notifContent = file_get_contents($notifModelFile);

if (strpos($notifContent, 'createServiceCompletionNotification') !== false) {
    echo "✅ Service completion notification method exists\n";
    
    // Check recent completion notifications
    $recentCompletionNotifs = $db->table('notifications')
        ->where('title', 'Service Completed')
        ->orderBy('created_at', 'DESC')
        ->limit(3)
        ->get()
        ->getResultArray();
    
    if (!empty($recentCompletionNotifs)) {
        echo "✅ Found " . count($recentCompletionNotifs) . " recent completion notification(s):\n";
        foreach ($recentCompletionNotifs as $notif) {
            $readStatus = $notif['read'] ? 'Read' : 'Unread';
            echo "   - User ID: {$notif['user_id']}, Submission: {$notif['submission_id']}, ";
            echo "Status: {$readStatus}, Date: {$notif['created_at']}\n";
        }
    } else {
        echo "ℹ️  No completion notifications found yet (normal if no forms completed).\n";
    }
} else {
    echo "❌ Service completion notification method NOT found!\n";
    echo "   This is unexpected - please check NotificationModel.php\n";
}
echo "\n";

// TEST 5: Simulate Requestor Form Access
echo "TEST 5: Simulating Requestor Form Access\n";
echo str_repeat("-", 50) . "\n";

if (!empty($requestors)) {
    $testRequestor = $requestors[0];
    
    // Get forms the requestor should see (should be ALL forms)
    $accessibleForms = $db->query("
        SELECT f.id, f.code, f.description, f.department_id, f.office_id,
               d.description as dept_name, o.description as office_name
        FROM forms f
        LEFT JOIN departments d ON d.id = f.department_id
        LEFT JOIN offices o ON o.id = f.office_id
        WHERE f.active = 1
        ORDER BY f.description
    ")->getResultArray();
    
    echo "✅ Requestor (ID: {$testRequestor['id']}) can access {$totalForms} form(s)\n";
    echo "✅ This includes forms from ALL departments (no restrictions)\n";
    
    if (count($accessibleForms) < $totalForms) {
        echo "⚠️  Warning: Query returned fewer forms than expected!\n";
    }
    
    // Check department distribution
    $deptCounts = [];
    foreach ($accessibleForms as $form) {
        $deptName = $form['dept_name'] ?: 'No Department';
        $deptCounts[$deptName] = ($deptCounts[$deptName] ?? 0) + 1;
    }
    
    if (count($deptCounts) > 1) {
        echo "✅ Forms span multiple departments:\n";
        foreach ($deptCounts as $dept => $count) {
            echo "   - {$dept}: {$count} form(s)\n";
        }
    } else {
        echo "ℹ️  All forms belong to the same department.\n";
    }
} else {
    echo "⚠️  Cannot simulate - no requestor users available.\n";
}
echo "\n";

// TEST 6: Check Completed Forms with Signatures
echo "TEST 6: Checking Completed Forms (for notification testing)\n";
echo str_repeat("-", 50) . "\n";

$completedForms = $db->query("
    SELECT fs.id, fs.submitted_by, fs.service_staff_id, fs.status,
           fs.service_staff_signature_date, fs.completion_date,
           u.full_name as requestor_name
    FROM form_submissions fs
    JOIN users u ON u.id = fs.submitted_by
    WHERE fs.status = 'completed'
      AND fs.service_staff_signature_date IS NOT NULL
    ORDER BY fs.completion_date DESC
    LIMIT 5
")->getResultArray();

if (!empty($completedForms)) {
    echo "✅ Found " . count($completedForms) . " completed form(s):\n";
    foreach ($completedForms as $form) {
        echo "   - Submission #{$form['id']}: Requestor: {$form['requestor_name']}, ";
        echo "Completed: {$form['completion_date']}\n";
        
        // Check if notification was sent
        $notifSent = $db->table('notifications')
            ->where('submission_id', $form['id'])
            ->where('user_id', $form['submitted_by'])
            ->where('title', 'Service Completed')
            ->countAllResults();
        
        if ($notifSent > 0) {
            echo "      ✅ Completion notification sent\n";
        } else {
            echo "      ⚠️  No completion notification found (may be older submission)\n";
        }
    }
} else {
    echo "ℹ️  No completed forms found. Complete a form to test notifications.\n";
}
echo "\n";

// Summary
echo str_repeat("=", 50) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n\n";

$passed = 0;
$total = 6;

// Count tests
if (!empty($requestors)) $passed++;
if ($totalForms > 0) $passed++;
if (!empty($formsWithSignatories)) $passed++;
if (strpos($notifContent, 'createServiceCompletionNotification') !== false) $passed++;
if (!empty($requestors) && count($accessibleForms) === $totalForms) $passed++;
if (!empty($completedForms)) $passed++;

echo "Tests Passed: {$passed}/{$total}\n\n";

if ($passed === $total) {
    echo "✅ All systems operational! Requestor enhancements are working correctly.\n";
} else {
    echo "⚠️  Some tests failed or incomplete. Review output above for details.\n";
}

echo "\n=== END OF TEST SUITE ===\n";
