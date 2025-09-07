<?php
// Check CRSRF form with pivot table data
$mysqli = new mysqli('localhost', 'root', '', 'smartiso', 3306);
if ($mysqli->connect_error) { echo "CONNECT_ERROR: " . $mysqli->connect_error . PHP_EOL; exit(1); }

$sql = "SELECT f.id, f.code, f.department_id AS form_department_id, f.office_id AS form_office_id, 
  o.description AS office_name, d1.description AS form_department_name, 
  do.department_id AS pivot_department_id, d3.description AS pivot_department_name
  FROM forms f
  LEFT JOIN departments d1 ON d1.id = f.department_id
  LEFT JOIN offices o ON o.id = f.office_id
  LEFT JOIN department_office do ON do.office_id = o.id
  LEFT JOIN departments d3 ON d3.id = do.department_id
  WHERE f.code = 'CRSRF' LIMIT 10";

$res = $mysqli->query($sql);
if (!$res) { echo "QUERY_ERROR: " . $mysqli->error . PHP_EOL; exit(1); }
$out = [];
while ($row = $res->fetch_assoc()) { $out[] = $row; }
echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
$mysqli->close();
