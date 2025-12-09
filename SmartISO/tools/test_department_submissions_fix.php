<?php
$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  DEPARTMENT SUBMISSIONS PAGE FIX TEST                        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Get IT dept admin
$query = "SELECT id, username, department_id FROM users WHERE username = 'dept_admin_it'";
$result = $pdo->query($query);
$admin = $result->fetch(PDO::FETCH_ASSOC);

echo "Testing for: IT Department Admin\n";
echo "  User ID: {$admin['id']}\n";
echo "  Department: IT (ID: {$admin['department_id']})\n\n";

echo "─────────────────────────────────────────────────────────────\n";
echo "TEST 1: OLD QUERY (Filter by Requestor's Department)\n";
echo "─────────────────────────────────────────────────────────────\n\n";

// Get users from IT department
$userQuery = "SELECT GROUP_CONCAT(id) as user_ids FROM users WHERE department_id = {$admin['department_id']}";
$userResult = $pdo->query($userQuery);
$userIds = $userResult->fetch(PDO::FETCH_ASSOC)['user_ids'];

if ($userIds) {
    $oldQuery = "SELECT fs.id, fs.status, f.code as form_code, f.description as form_desc,
                        u.full_name as requestor, u.department_id as requestor_dept
                 FROM form_submissions fs
                 LEFT JOIN forms f ON fs.form_id = f.id
                 LEFT JOIN users u ON fs.submitted_by = u.id
                 WHERE fs.submitted_by IN ({$userIds})
                 ORDER BY fs.created_at DESC
                 LIMIT 20";
    
    $result = $pdo->query($oldQuery);
    $oldResults = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Submissions by users FROM IT department: " . count($oldResults) . "\n";
    if (count($oldResults) > 0) {
        echo "  (Shows submissions made BY IT users, regardless of which form)\n";
        foreach (array_slice($oldResults, 0, 5) as $row) {
            echo "  - Submission {$row['id']}: {$row['form_code']} | Status: {$row['status']} | By: {$row['requestor']}\n";
        }
    }
} else {
    echo "No users in IT department\n";
    $oldResults = [];
}

echo "\n─────────────────────────────────────────────────────────────\n";
echo "TEST 2: NEW QUERY (Filter by Form's Department)\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$newQuery = "SELECT fs.id, fs.status, f.code as form_code, f.description as form_desc,
                    f.department_id as form_dept,
                    u.full_name as requestor, u.department_id as requestor_dept
             FROM form_submissions fs
             LEFT JOIN forms f ON fs.form_id = f.id
             LEFT JOIN users u ON fs.submitted_by = u.id
             WHERE f.department_id = {$admin['department_id']}
             ORDER BY fs.created_at DESC
             LIMIT 20";

$result = $pdo->query($newQuery);
$newResults = $result->fetchAll(PDO::FETCH_ASSOC);

echo "Submissions TO forms owned BY IT department: " . count($newResults) . "\n";
if (count($newResults) > 0) {
    echo "  (Shows submissions to IT-owned forms, regardless of who submitted)\n";
    foreach (array_slice($newResults, 0, 5) as $row) {
        echo "  - Submission {$row['id']}: {$row['form_code']} | Status: {$row['status']} | By: {$row['requestor']} (Dept {$row['requestor_dept']})\n";
    }
} else {
    echo "  ✓ PASS: No submissions (IT has no form submissions yet)\n";
}

echo "\n─────────────────────────────────────────────────────────────\n";
echo "TEST 3: CRSRF Submissions Check\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$crsrfQuery = "SELECT COUNT(*) as count, f.department_id
               FROM form_submissions fs
               LEFT JOIN forms f ON fs.form_id = f.id
               WHERE f.code = 'CRSRF'
               GROUP BY f.department_id";

$result = $pdo->query($crsrfQuery);
$crsrfInfo = $result->fetch(PDO::FETCH_ASSOC);

echo "CRSRF form belongs to department: {$crsrfInfo['department_id']}\n";
echo "CRSRF submissions count: {$crsrfInfo['count']}\n\n";

if ($crsrfInfo['department_id'] == $admin['department_id']) {
    echo "⚠️ CRSRF belongs to IT - IT admin SHOULD see these\n";
} else {
    echo "✓ CRSRF belongs to department {$crsrfInfo['department_id']} - IT admin should NOT see these\n";
}

echo "\n─────────────────────────────────────────────────────────────\n";
echo "TEST 4: Forms Owned by IT Department\n";
echo "─────────────────────────────────────────────────────────────\n\n";

$formsQuery = "SELECT f.id, f.code, f.description,
                      (SELECT COUNT(*) FROM form_submissions WHERE form_id = f.id) as submission_count
               FROM forms f
               WHERE f.department_id = {$admin['department_id']}";

$result = $pdo->query($formsQuery);
$itForms = $result->fetchAll(PDO::FETCH_ASSOC);

echo "Forms owned by IT department: " . count($itForms) . "\n";
foreach ($itForms as $form) {
    echo "  - {$form['code']}: {$form['description']}\n";
    echo "    Submissions: {$form['submission_count']}\n";
}

echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║  TEST SUMMARY                                                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "OLD Logic (by requestor dept): " . count($oldResults) . " submissions\n";
echo "NEW Logic (by form dept): " . count($newResults) . " submissions\n\n";

if (count($newResults) == 0) {
    echo "✓ PASS: IT admin sees 0 submissions (correct)\n";
    echo "  IT department owns FORM2123 with 0 submissions\n";
} else {
    echo "✓ IT admin sees " . count($newResults) . " submissions for IT-owned forms\n";
}

echo "\n🎯 EXPECTED BEHAVIOR:\n";
echo "  - IT admin sees submissions for forms OWNED by IT department\n";
echo "  - IT admin does NOT see CRSRF submissions (owned by Administration)\n";
echo "  - Any user can submit to any form, but dept admins only see their forms\n";
