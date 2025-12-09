<?php
$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== TESTING DEPARTMENT FILTERING FIX ===\n\n";

// Get IT dept admin info
$query = "SELECT id, username, department_id FROM users WHERE username = 'dept_admin_it'";
$result = $pdo->query($query);
$admin = $result->fetch(PDO::FETCH_ASSOC);

echo "Testing for IT Department Admin:\n";
echo "  User ID: " . $admin['id'] . "\n";
echo "  Department ID: " . $admin['department_id'] . "\n\n";

echo "=== OLD QUERY (Filter by Requestor Department) ===\n";
$oldQuery = "SELECT s.id as schedule_id, s.submission_id,
                    u.department_id as requestor_department_id,
                    f.code as form_code, f.description as form_desc,
                    fs.status
             FROM schedules s
             LEFT JOIN form_submissions fs ON s.submission_id = fs.id
             LEFT JOIN forms f ON fs.form_id = f.id
             LEFT JOIN users u ON fs.submitted_by = u.id
             WHERE u.department_id = " . $admin['department_id'] . "
             ORDER BY s.id DESC";

$result = $pdo->query($oldQuery);
$oldResults = $result->fetchAll(PDO::FETCH_ASSOC);

echo "Results: " . count($oldResults) . " schedules\n";
if (!empty($oldResults)) {
    foreach ($oldResults as $row) {
        echo "  - Schedule " . $row['schedule_id'] . ": " . $row['form_code'] . " (Submission " . $row['submission_id'] . ") | Status: " . $row['status'] . "\n";
    }
} else {
    echo "  (No results)\n";
}

echo "\n=== NEW QUERY (Filter by Form Department) ===\n";
$newQuery = "SELECT s.id as schedule_id, s.submission_id,
                    f.department_id as form_department_id,
                    u.department_id as requestor_department_id,
                    f.code as form_code, f.description as form_desc,
                    fs.status
             FROM schedules s
             LEFT JOIN form_submissions fs ON s.submission_id = fs.id
             LEFT JOIN forms f ON fs.form_id = f.id
             LEFT JOIN users u ON fs.submitted_by = u.id
             WHERE f.department_id = " . $admin['department_id'] . "
             ORDER BY s.id DESC";

$result = $pdo->query($newQuery);
$newResults = $result->fetchAll(PDO::FETCH_ASSOC);

echo "Results: " . count($newResults) . " schedules\n";
if (!empty($newResults)) {
    foreach ($newResults as $row) {
        echo "  - Schedule " . $row['schedule_id'] . ": " . $row['form_code'] . " (Submission " . $row['submission_id'] . ") | Status: " . $row['status'] . "\n";
        echo "    Form Dept: " . $row['form_department_id'] . " | Requestor Dept: " . $row['requestor_department_id'] . "\n";
    }
} else {
    echo "  (No results)\n";
}

echo "\n=== COMPARISON ===\n";
echo "Old Query (by requestor dept): " . count($oldResults) . " schedules\n";
echo "New Query (by form dept): " . count($newResults) . " schedules\n";

if (count($oldResults) > count($newResults)) {
    echo "\n✓ FIX WORKING: Reduced from " . count($oldResults) . " to " . count($newResults) . " schedules\n";
    echo "  IT Department Admin should only see forms that BELONG to IT department.\n";
} elseif (count($oldResults) < count($newResults)) {
    echo "\n⚠️ UNEXPECTED: New query returns MORE schedules\n";
} else {
    echo "\n✓ Same count - checking if forms are different\n";
}

echo "\n=== FORMS BELONGING TO IT DEPARTMENT ===\n";
$formQuery = "SELECT f.id, f.code, f.description, f.department_id, d.description as dept_name
              FROM forms f
              LEFT JOIN departments d ON f.department_id = d.id
              WHERE f.department_id = " . $admin['department_id'];
$result = $pdo->query($formQuery);
$forms = $result->fetchAll(PDO::FETCH_ASSOC);

echo "Forms owned by IT department:\n";
foreach ($forms as $form) {
    echo "  - " . $form['code'] . ": " . $form['description'] . "\n";
    
    // Count submissions for this form
    $countQuery = "SELECT COUNT(*) as count FROM form_submissions WHERE form_id = " . $form['id'];
    $countResult = $pdo->query($countQuery);
    $count = $countResult->fetch(PDO::FETCH_ASSOC);
    echo "    Submissions: " . $count['count'] . "\n";
    
    // Count schedules for this form
    $schedQuery = "SELECT COUNT(*) as count 
                   FROM schedules s
                   LEFT JOIN form_submissions fs ON s.submission_id = fs.id
                   WHERE fs.form_id = " . $form['id'];
    $schedResult = $pdo->query($schedQuery);
    $schedCount = $schedResult->fetch(PDO::FETCH_ASSOC);
    echo "    Schedules: " . $schedCount['count'] . "\n";
}

echo "\n✓ Department admins should ONLY see schedules for forms that belong to their department.\n";
echo "✓ This is correct because departments OWN forms and manage their submissions.\n";
