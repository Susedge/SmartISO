<?php
/**
 * Debug script to inspect a form, its office/department ids, and the office/department records.
 * Run: php tools/debug_form_filtering_mysqli.php <form_code>
 */
if ($argc < 2) {
    echo "Usage: php tools/debug_form_filtering_mysqli.php <form_code>\n";
    exit(1);
}
$formCode = $argv[1];
$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';
$dbname = 'smartiso';
$mysqli = new mysqli($host, $user, $pass, $dbname, $port);
if ($mysqli->connect_errno) { echo "Connect failed: " . $mysqli->connect_error . "\n"; exit(1); }

// Find form by code
$stmt = $mysqli->prepare("SELECT id, code, description, office_id, department_id FROM forms WHERE code = ? LIMIT 1");
$stmt->bind_param('s', $formCode);
$stmt->execute();
$res = $stmt->get_result();
$form = $res->fetch_assoc();
if (!$form) { echo "Form not found: {$formCode}\n"; exit(1); }

echo "Form: id={$form['id']} code={$form['code']} desc={$form['description']} office_id={$form['office_id']} department_id={$form['department_id']}\n";

$officeId = $form['office_id'];
$deptId = $form['department_id'];

if ($officeId) {
    $stmt = $mysqli->prepare("SELECT id, code, description, department_id FROM offices WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $officeId);
    $stmt->execute();
    $res = $stmt->get_result();
    $office = $res->fetch_assoc();
    if ($office) {
        echo "Office: id={$office['id']} code={$office['code']} desc={$office['description']} department_id={$office['department_id']}\n";
    } else {
        echo "Office id {$officeId} not found\n";
    }
} else {
    echo "Form has no office_id set\n";
}

if ($deptId) {
    $stmt = $mysqli->prepare("SELECT id, code, description FROM departments WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $deptId);
    $stmt->execute();
    $res = $stmt->get_result();
    $dept = $res->fetch_assoc();
    if ($dept) {
        echo "Department: id={$dept['id']} code={$dept['code']} desc={$dept['description']}\n";
    } else {
        echo "Department id {$deptId} not found\n";
    }
} else {
    echo "Form has no department_id set\n";
}

// Print all offices for the department to see mapping
if ($deptId) {
    $stmt = $mysqli->prepare("SELECT id, code, description FROM offices WHERE department_id = ? ORDER BY description ASC");
    $stmt->bind_param('i', $deptId);
    $stmt->execute();
    $res = $stmt->get_result();
    echo "Offices belonging to department {$deptId}:\n";
    while ($r = $res->fetch_assoc()) {
        echo "  id={$r['id']} code={$r['code']} desc={$r['description']}\n";
    }
}

// Also print the office's department name if office exists
if (!empty($office) && $office['department_id']) {
    $stmt = $mysqli->prepare("SELECT id, code, description FROM departments WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $office['department_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $offDept = $res->fetch_assoc();
    if ($offDept) echo "Office's Department: id={$offDept['id']} code={$offDept['code']} desc={$offDept['description']}\n";
}

$mysqli->close();
