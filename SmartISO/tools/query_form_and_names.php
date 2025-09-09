<?php
// Temporary debug script - non committed
// Usage: php query_form_and_names.php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'smartiso', 3306);
if ($mysqli->connect_errno) {
    echo "DB connect failed: " . $mysqli->connect_error . "\n";
    exit(1);
}
// Look up departments and offices for 'Finance' and 'Finance Office', and the form code 'CRSRF'
$deptName = 'Finance';
$officeName = 'Finance Office';
$formCode = 'CRSRF';

function lookupId($mysqli, $table, $nameCol, $name) {
    $stmt = $mysqli->prepare("SELECT id, code, description FROM {$table} WHERE description = ? OR code = ? LIMIT 1");
    $stmt->bind_param('ss', $name, $name);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res ?: null;
}

$dDept = lookupId($mysqli, 'departments', 'description', $deptName);
$offRow = lookupId($mysqli, 'offices', 'description', $officeName);
$formRow = null;
$stmt = $mysqli->prepare('SELECT id, code, description, department_id, office_id FROM forms WHERE code = ? LIMIT 1');
$stmt->bind_param('s', $formCode);
$stmt->execute();
$formRow = $stmt->get_result()->fetch_assoc();

echo "Department lookup for '{$deptName}':\n";
if ($dDept) { echo json_encode($dDept) . "\n"; } else { echo "NOT FOUND\n"; }

echo "Office lookup for '{$officeName}':\n";
if ($offRow) { echo json_encode($offRow) . "\n"; } else { echo "NOT FOUND\n"; }

echo "Form lookup for code '{$formCode}':\n";
if ($formRow) { echo json_encode($formRow) . "\n"; } else { echo "NOT FOUND\n"; }

// Also list the office's department if available
if ($offRow && isset($offRow['id'])) {
    $stmt = $mysqli->prepare('SELECT department_id, description FROM offices WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $offRow['id']);
    $stmt->execute();
    $o = $stmt->get_result()->fetch_assoc();
    echo "Office row (by id): " . json_encode($o) . "\n";
}

// Show any other forms that match office_id or department_id to see related rows
if ($offRow && isset($offRow['id'])) {
    $oid = (int)$offRow['id'];
    $res = $mysqli->query("SELECT id, code, description, department_id, office_id FROM forms WHERE office_id = {$oid}");
    echo "Forms with office_id={$oid}: count=" . $res->num_rows . "\n";
    while ($r = $res->fetch_assoc()) { echo json_encode($r) . "\n"; }
}
if ($dDept && isset($dDept['id'])) {
    $did = (int)$dDept['id'];
    $res = $mysqli->query("SELECT id, code, description, department_id, office_id FROM forms WHERE department_id = {$did}");
    echo "Forms with department_id={$did}: count=" . $res->num_rows . "\n";
    while ($r = $res->fetch_assoc()) { echo json_encode($r) . "\n"; }
}

$mysqli->close();
