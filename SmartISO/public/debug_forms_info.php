<?php
// Temporary debug endpoint. Remove after use.
header('Content-Type: application/json');
$mysqli = new mysqli('127.0.0.1','root','', 'smartiso', 3306);
if ($mysqli->connect_errno) {
    echo json_encode(['error'=>'DB connect failed','message'=>$mysqli->connect_error]);
    exit;
}
$deptName = 'Administration';
$officeLike = '%Information Technology%';
$formCode = 'CRSRF';

function rowOrNull($res){ return $res ? $res->fetch_assoc() : null; }

// department
$stmt = $mysqli->prepare("SELECT id, code, description FROM departments WHERE description = ? OR code = ? LIMIT 1");
$stmt->bind_param('ss', $deptName, $deptName);
$stmt->execute();
$dept = rowOrNull($stmt->get_result());

// office (match by description LIKE)
$stmt = $mysqli->prepare("SELECT id, code, description, department_id FROM offices WHERE description LIKE ? LIMIT 1");
$stmt->bind_param('s', $officeLike);
$stmt->execute();
$office = rowOrNull($stmt->get_result());

// form by code
$stmt = $mysqli->prepare("SELECT id, code, description, department_id, office_id FROM forms WHERE code = ? LIMIT 1");
$stmt->bind_param('s', $formCode);
$stmt->execute();
$form = rowOrNull($stmt->get_result());

// intersection query
$intersection = [];
if ($dept && $office) {
    $did = (int)$dept['id'];
    $oid = (int)$office['id'];
    $sql = "SELECT f.id,f.code,f.description,f.department_id AS form_dept_id,f.office_id AS form_office_id, d1.description AS form_dept_name, o.description AS office_name, d2.description AS office_dept_name
            FROM forms f
            LEFT JOIN departments d1 ON d1.id = f.department_id
            LEFT JOIN offices o ON o.id = f.office_id
            LEFT JOIN departments d2 ON d2.id = o.department_id
            WHERE f.office_id = {$oid} AND (f.department_id = {$did} OR o.department_id = {$did})
            ORDER BY f.description";
    $res = $mysqli->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) { $intersection[] = $r; }
    } else { $intersection = ['error'=>$mysqli->error]; }
}

// forms matching office only
$forms_office = [];
if ($office) {
    $oid = (int)$office['id'];
    $res = $mysqli->query("SELECT id,code,description,department_id,office_id FROM forms WHERE office_id = {$oid}");
    if ($res) { while ($r=$res->fetch_assoc()) $forms_office[]=$r; }
}
// forms matching department only
$forms_dept = [];
if ($dept) {
    $did = (int)$dept['id'];
    $res = $mysqli->query("SELECT id,code,description,department_id,office_id FROM forms WHERE department_id = {$did}");
    if ($res) { while ($r=$res->fetch_assoc()) $forms_dept[]=$r; }
}

echo json_encode([
    'department_lookup' => $dept,
    'office_lookup' => $office,
    'form_lookup' => $form,
    'intersection_query' => $intersection,
    'forms_with_office' => $forms_office,
    'forms_with_department' => $forms_dept
], JSON_PRETTY_PRINT);
$mysqli->close();
