<?php
/**
 * check_forms_filter.php
 *
 * Usage (PowerShell/CMD):
 *   php tools\check_forms_filter.php "Administration" "Information Technology" CRSRF
 * Or pass numeric ids:
 *   php tools\check_forms_filter.php --dept-id=12 --office-id=3 --form=CRSRF
 *
 * The script connects to MySQL (defaults provided) and prints a JSON report
 * showing dept/office/form lookups and the intersection query used by the app.
 *
 * Options (environment variables supported):
 *  DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT
 */

// Simple CLI arg parsing
$options = getopt('', ['dept-id::','office-id::','form::']);
$args = array_values(array_filter($argv, function($v){ return strpos($v,'--') !== 0; }));
// Remove script name
array_shift($args);

$defaultDept = $args[0] ?? null;
$defaultOffice = $args[1] ?? null;
$defaultForm = $args[2] ?? null;

// Support --dept-id and --office-id which will be in $options
$deptIdOpt = isset($options['dept-id']) && $options['dept-id'] !== false ? $options['dept-id'] : null;
$officeIdOpt = isset($options['office-id']) && $options['office-id'] !== false ? $options['office-id'] : null;
$formCode = $options['form'] ?? $defaultForm ?? 'CRSRF';

$deptQueryVal = $defaultDept; // may be name or id
$officeQueryVal = $defaultOffice;

if ($deptIdOpt) { $deptQueryVal = (int)$deptIdOpt; }
if ($officeIdOpt) { $officeQueryVal = (int)$officeIdOpt; }

// DB connection params (allow env overrides)
$DB_HOST = getenv('DB_HOST') ?: getenv('DATABASE_HOST') ?: '127.0.0.1';
$DB_USER = getenv('DB_USER') ?: getenv('DATABASE_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: getenv('DATABASE_PASS') ?: '';
$DB_NAME = getenv('DB_NAME') ?: getenv('DATABASE_NAME') ?: 'smartiso';
$DB_PORT = getenv('DB_PORT') ?: 3306;

$report = [
    'meta' => [
        'dept_input' => $deptQueryVal,
        'office_input' => $officeQueryVal,
        'form_code' => $formCode,
        'db' => [ 'host'=>$DB_HOST, 'user'=>$DB_USER, 'name'=>$DB_NAME, 'port'=>$DB_PORT ]
    ],
    'errors' => [],
    'results' => new stdClass()
];

$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, (int)$DB_PORT);
if ($mysqli->connect_errno) {
    $report['errors'][] = 'DB connect failed: ' . $mysqli->connect_error;
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}
$mysqli->set_charset('utf8mb4');

// Helper: fetch one row using prepared statement (name or id)
function lookupDepartment($mysqli, $val) {
    if ($val === null) return null;
    if (is_numeric($val)) {
        $id = (int)$val;
        $stmt = $mysqli->prepare('SELECT id,code,description FROM departments WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return $r ?: null;
    } else {
        $s = trim($val);
        $stmt = $mysqli->prepare('SELECT id,code,description FROM departments WHERE description = ? OR code = ? LIMIT 1');
        $stmt->bind_param('ss', $s, $s);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return $r ?: null;
    }
}

function lookupOffice($mysqli, $val) {
    if ($val === null) return null;
    if (is_numeric($val)) {
        $id = (int)$val;
        $stmt = $mysqli->prepare('SELECT id,code,description,department_id FROM offices WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return $r ?: null;
    } else {
        $s = trim($val);
        // try exact, then LIKE
        $stmt = $mysqli->prepare('SELECT id,code,description,department_id FROM offices WHERE description = ? OR code = ? LIMIT 1');
        $stmt->bind_param('ss', $s, $s);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        if ($r) return $r;
        $like = '%' . $s . '%';
        $stmt = $mysqli->prepare('SELECT id,code,description,department_id FROM offices WHERE description LIKE ? LIMIT 1');
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return $r ?: null;
    }
}

function lookupFormByCode($mysqli, $code) {
    if (!$code) return null;
    $c = trim($code);
    $stmt = $mysqli->prepare('SELECT id,code,description,department_id,office_id FROM forms WHERE code = ? LIMIT 1');
    $stmt->bind_param('s', $c);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

$d = lookupDepartment($mysqli, $deptQueryVal);
$o = lookupOffice($mysqli, $officeQueryVal);
$f = lookupFormByCode($mysqli, $formCode);

$report['results']->department = $d;
$report['results']->office = $o;
$report['results']->form = $f;

// Check department_office pivot if exists
$report['results']->pivot_entries = [];
$tablesRes = $mysqli->query("SHOW TABLES LIKE 'department_office'");
if ($tablesRes && $tablesRes->num_rows > 0) {
    $report['meta']['pivot_exists'] = true;
    if ($o && isset($o['id'])) {
        $oid = (int)$o['id'];
        $res = $mysqli->query("SELECT department_id, office_id FROM department_office WHERE office_id = " . $oid);
        if ($res) {
            while ($r = $res->fetch_assoc()) $report['results']->pivot_entries[] = $r;
        }
    }
} else {
    $report['meta']['pivot_exists'] = false;
}

// Intersection query if both ids available
$report['results']->intersection = [];
if ($d && $o) {
    $did = (int)$d['id'];
    $oid = (int)$o['id'];
    $sql = "SELECT f.id,f.code,f.description,f.department_id AS form_dept_id,f.office_id AS form_office_id, d1.description AS form_dept_name, o.description AS office_name, d2.description AS office_dept_name
            FROM forms f
            LEFT JOIN departments d1 ON d1.id = f.department_id
            LEFT JOIN offices o ON o.id = f.office_id
            LEFT JOIN departments d2 ON d2.id = o.department_id
            WHERE f.office_id = {$oid} AND (f.department_id = {$did} OR o.department_id = {$did})
            ORDER BY f.description";
    $res = $mysqli->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) $report['results']->intersection[] = $r;
    } else {
        $report['errors'][] = 'Intersection query failed: ' . $mysqli->error;
    }
}

// Also list forms for office and for department separately
$report['results']->forms_for_office = [];
if ($o) {
    $oid = (int)$o['id'];
    $res = $mysqli->query("SELECT id,code,description,department_id,office_id FROM forms WHERE office_id = {$oid}");
    if ($res) { while ($r = $res->fetch_assoc()) $report['results']->forms_for_office[] = $r; }
}
$report['results']->forms_for_department = [];
if ($d) {
    $did = (int)$d['id'];
    $res = $mysqli->query("SELECT id,code,description,department_id,office_id FROM forms WHERE department_id = {$did}");
    if ($res) { while ($r = $res->fetch_assoc()) $report['results']->forms_for_department[] = $r; }
}

// Echo report
echo json_encode($report, JSON_PRETTY_PRINT);

$mysqli->close();

// Done
