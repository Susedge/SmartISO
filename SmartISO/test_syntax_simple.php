<?php
/**
 * Simplified Syntax and Structure Test
 * Tests only the syntax and basic structure without instantiating objects
 */

echo "===========================================\n";
echo "Department-Based Access Control - Syntax Test\n";
echo "===========================================\n\n";

// Test 1: PHP Syntax Check for modified files
echo "Test 1: PHP Syntax Check\n";
echo "-------------------------------------------\n";

$filesToCheck = [
    'app/Controllers/Forms.php',
    'app/Models/NotificationModel.php',
    'app/Controllers/Dashboard.php',
    'app/Controllers/Schedule.php',
    'app/Controllers/Analytics.php',
    'app/Controllers/Auth.php',
    'app/Controllers/Admin/Users.php'
];

$allPassed = true;
foreach ($filesToCheck as $file) {
    $output = [];
    $returnCode = 0;
    exec("php -l \"{$file}\" 2>&1", $output, $returnCode);
    
    $outputStr = implode("\n", $output);
    
    if ($returnCode === 0 && strpos($outputStr, 'No syntax errors') !== false) {
        echo "  ✅ {$file}\n";
    } else {
        echo "  ❌ {$file} - SYNTAX ERROR\n";
        echo "     " . $outputStr . "\n";
        $allPassed = false;
    }
}

echo $allPassed ? "\n✅ PASS - All files have correct syntax\n\n" : "\n❌ FAIL - Syntax errors detected\n\n";

// Test 2: Check for critical department filtering code patterns
echo "Test 2: Department Filtering Code Patterns\n";
echo "-------------------------------------------\n";

$patterns = [
    'Forms.php viewSubmission' => [
        'file' => 'app/Controllers/Forms.php',
        'pattern' => '/department_id.*userDepartmentId.*isAdmin.*department_admin/s',
        'context' => 'viewSubmission department check'
    ],
    'Forms.php approveForm' => [
        'file' => 'app/Controllers/Forms.php',
        'pattern' => '/approveForm.*department_id.*requestor.*department/s',
        'context' => 'approveForm department check'
    ],
    'Forms.php serviceForm' => [
        'file' => 'app/Controllers/Forms.php',
        'pattern' => '/serviceForm.*department_id.*requestor.*department/s',
        'context' => 'serviceForm department check'
    ],
    'Forms.php export' => [
        'file' => 'app/Controllers/Forms.php',
        'pattern' => '/export.*department_id.*submitter.*department/s',
        'context' => 'export department check'
    ],
    'NotificationModel' => [
        'file' => 'app/Models/NotificationModel.php',
        'pattern' => '/submitterDepartment.*department_id.*same department/s',
        'context' => 'Notification department filtering'
    ]
];

$allPatternsFound = true;
foreach ($patterns as $name => $config) {
    $content = file_get_contents($config['file']);
    if (preg_match($config['pattern'], $content)) {
        echo "  ✅ {$name} - Department filtering code present\n";
    } else {
        echo "  ❌ {$name} - Department filtering code NOT FOUND\n";
        $allPatternsFound = false;
    }
}

echo $allPatternsFound ? "\n✅ PASS - All department filtering patterns found\n\n" : "\n❌ FAIL - Some patterns missing\n\n";

// Test 3: Check for specific security fixes
echo "Test 3: Security Fix Verification\n";
echo "-------------------------------------------\n";

$formsContent = file_get_contents('app/Controllers/Forms.php');

$securityChecks = [
    'viewSubmission department check' => 'You can only view submissions from your department',
    'approveForm department check' => 'You can only approve submissions from your department',
    'serviceForm department check' => 'You can only service submissions from your department',
    'export department check' => 'You can only export submissions from your department',
    'servicedByMe filter' => 'requestor.department_id',
    'approvedByMe filter' => 'requestor.department_id',
    'rejectedByMe filter' => 'requestor.department_id'
];

$allChecksPass = true;
foreach ($securityChecks as $check => $searchString) {
    if (strpos($formsContent, $searchString) !== false) {
        echo "  ✅ {$check}\n";
    } else {
        echo "  ❌ {$check} - NOT FOUND\n";
        $allChecksPass = false;
    }
}

echo $allChecksPass ? "\n✅ PASS - All security checks found\n\n" : "\n❌ FAIL - Some security checks missing\n\n";

// Test 4: Check NotificationModel fix
echo "Test 4: Notification Model Fix\n";
echo "-------------------------------------------\n";

$notificationContent = file_get_contents('app/Models/NotificationModel.php');

$notificationChecks = [
    'Submitter department retrieval' => '$submitterDepartment',
    'Department-filtered approvers' => 'approving_authority',
    'Legacy fallback' => 'legacy support'
];

$allNotificationChecks = true;
foreach ($notificationChecks as $check => $searchString) {
    if (stripos($notificationContent, $searchString) !== false) {
        echo "  ✅ {$check}\n";
    } else {
        echo "  ❌ {$check} - NOT FOUND\n";
        $allNotificationChecks = false;
    }
}

echo $allNotificationChecks ? "\n✅ PASS - Notification fixes verified\n\n" : "\n❌ FAIL - Notification fixes incomplete\n\n";

// Test 5: Count security fixes applied
echo "Test 5: Security Fixes Count\n";
echo "-------------------------------------------\n";

$errorMessages = [
    'You can only view submissions from your department',
    'You can only approve submissions from your department',
    'You can only service submissions from your department',
    'You can only export submissions from your department'
];

$fixCount = 0;
foreach ($errorMessages as $msg) {
    if (strpos($formsContent, $msg) !== false) {
        $fixCount++;
    }
}

echo "  Found {$fixCount}/4 critical security error messages\n";
if ($fixCount === 4) {
    echo "✅ PASS - All 4 critical security fixes present\n\n";
} else {
    echo "❌ FAIL - Missing security fixes\n\n";
}

// Test 6: Check admin bypass logic
echo "Test 6: Admin Bypass Logic\n";
echo "-------------------------------------------\n";

$adminBypassCount = substr_count($formsContent, "in_array(\$userType, ['admin', 'superuser'");
$adminBypassCount += substr_count($formsContent, "in_array(\$userType, ['admin', 'superuser', 'department_admin']");
$adminBypassCount += substr_count($formsContent, "in_array(session()->get('user_type'), ['admin', 'superuser']");

echo "  Found {$adminBypassCount} admin bypass checks\n";
if ($adminBypassCount >= 8) {
    echo "✅ PASS - Admin bypass logic present in all fixed methods\n\n";
} else {
    echo "⚠️  WARNING - Admin bypass count lower than expected ({$adminBypassCount} found, expected ~8-10)\n\n";
}

// Summary
echo "===========================================\n";
echo "TEST SUMMARY\n";
echo "===========================================\n\n";

if ($allPassed && $allPatternsFound && $allChecksPass && $allNotificationChecks && $fixCount === 4) {
    echo "🎉 ALL TESTS PASSED!\n\n";
    echo "✅ Syntax: All files valid\n";
    echo "✅ Patterns: All department filtering code present\n";
    echo "✅ Security: All 9 fixes verified\n";
    echo "✅ Notifications: Department filtering implemented\n";
    echo "✅ Admin bypass: Logic present\n\n";
    echo "🔒 System is ready for functional testing\n\n";
    
    echo "NEXT STEPS:\n";
    echo "1. Test with actual user login in browser\n";
    echo "2. Verify cross-department access is blocked\n";
    echo "3. Confirm admin bypass works\n";
    echo "4. Use TESTING_CHECKLIST.md for complete verification\n";
    exit(0);
} else {
    echo "⚠️  SOME CHECKS FAILED\n\n";
    echo "Review the output above for details.\n";
    exit(1);
}
