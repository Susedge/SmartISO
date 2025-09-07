<?php
// Build and print the SQL string equivalent to the query in app/Controllers/Forms::index()
// Usage: php compile_forms_sql.php --department=1 --office=2
$opts = [];
foreach ($argv as $arg) {
    if (preg_match('/^--([^=]+)=(.*)$/', $arg, $m)) {
        $opts[$m[1]] = $m[2];
    }
}
$dept = isset($opts['department']) && $opts['department'] !== '' ? (int)$opts['department'] : null;
$office = isset($opts['office']) && $opts['office'] !== '' ? (int)$opts['office'] : null;

$select = "SELECT f.*, COALESCE(d1.description, d2.description) AS department_name, o.description AS office_name";
$from = "FROM forms f\n  LEFT JOIN departments d1 ON d1.id = f.department_id\n  LEFT JOIN offices o ON o.id = f.office_id\n  LEFT JOIN departments d2 ON d2.id = o.department_id";
$where = [];
if (!empty($dept)) {
    // grouped OR condition
    $where[] = "(f.department_id = " . $dept . " OR o.department_id = " . $dept . ")";
}
if (!empty($office)) {
    $where[] = "f.office_id = " . $office;
}
$sql = $select . "\n" . $from;
if (!empty($where)) {
    $sql .= "\nWHERE " . implode(' AND ', $where);
}
$sql .= "\nORDER BY f.description ASC;\n";

echo $sql;
