<?php
// Temporary debug script (non-committed)
$dept = $argv[1] ?? null;
$office = $argv[2] ?? null;
if (!$dept && !$office) {
    echo "Usage: php debug_forms_filter.php <department_id> <office_id>\n";
    exit(1);
}
$mysqli = new mysqli('127.0.0.1', 'root', '', 'smartiso', 3306);
if ($mysqli->connect_errno) {
    echo "DB connect failed: " . $mysqli->connect_error . "\n";
    exit(1);
}
$dept = is_numeric($dept) ? (int)$dept : null;
$office = is_numeric($office) ? (int)$office : null;
$query = "SELECT f.id,f.code,f.description,f.department_id AS form_dept_id,f.office_id AS form_office_id, d1.description AS form_dept_name, o.description AS office_name, d2.description AS office_dept_name
FROM forms f
LEFT JOIN departments d1 ON d1.id = f.department_id
LEFT JOIN offices o ON o.id = f.office_id
LEFT JOIN departments d2 ON d2.id = o.department_id";
$where = [];
if ($dept && $office) {
    $where[] = "(f.office_id = $office)";
    $where[] = "(f.department_id = $dept OR o.department_id = $dept)";
} else {
    if ($dept) {
        $where[] = "(f.department_id = $dept OR o.department_id = $dept)";
    }
    if ($office) {
        $where[] = "(f.office_id = $office)";
    }
}
if (!empty($where)) {
    $query .= " WHERE " . implode(' AND ', $where);
}
$query .= " ORDER BY f.description ASC";
echo "--- SQL ---\n" . $query . "\n\n";
if ($res = $mysqli->query($query)) {
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    echo "Found " . count($rows) . " rows\n\n";
    foreach ($rows as $r) {
        echo "id=" . $r['id'] . " code=" . $r['code'] . " desc=" . $r['description'] . " form_dept_id=" . ($r['form_dept_id'] ?? 'NULL') . " form_office_id=" . ($r['form_office_id'] ?? 'NULL') . " form_dept_name=" . ($r['form_dept_name'] ?? 'NULL') . " office_name=" . ($r['office_name'] ?? 'NULL') . " office_dept_name=" . ($r['office_dept_name'] ?? 'NULL') . "\n";
    }
} else {
    echo "Query failed: " . $mysqli->error . "\n";
}
$mysqli->close();
// cleanup left to user
