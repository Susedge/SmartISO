<?php
// Quick DB dump for form code CRSRF
$mysqli = new mysqli('localhost', 'root', '', 'smartiso', 3306);
if ($mysqli->connect_error) {
    echo "CONNECT_ERROR: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}
$sql = "SELECT f.id, f.code, f.department_id AS form_department_id, f.office_id AS form_office_id, 
  o.department_id AS office_department_id, d1.description AS form_department_name, d2.description AS office_department_name, o.description AS office_name
  FROM forms f
  LEFT JOIN departments d1 ON d1.id = f.department_id
  LEFT JOIN offices o ON o.id = f.office_id
  LEFT JOIN departments d2 ON d2.id = o.department_id
  WHERE f.code = 'CRSRF' LIMIT 10";
$res = $mysqli->query($sql);
if (!$res) {
    echo "QUERY_ERROR: " . $mysqli->error . PHP_EOL;
    exit(1);
}
$out = [];
while ($row = $res->fetch_assoc()) {
    $out[] = $row;
}
echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
$mysqli->close();
