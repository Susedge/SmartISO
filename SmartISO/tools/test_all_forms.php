<?php
// Direct database query to simulate what forms page should show with no filters
$mysqli = new mysqli('localhost', 'root', '', 'smartiso', 3306);
if ($mysqli->connect_error) { echo "CONNECT_ERROR: " . $mysqli->connect_error . PHP_EOL; exit(1); }

// This is the exact query from Forms controller with no filters
$sql = "SELECT f.*, COALESCE(d1.description, d3.description) AS department_name, o.description AS office_name
FROM forms f
LEFT JOIN departments d1 ON d1.id = f.department_id
LEFT JOIN offices o ON o.id = f.office_id
LEFT JOIN department_office do ON do.office_id = o.id
LEFT JOIN departments d3 ON d3.id = do.department_id
ORDER BY f.description ASC";

$res = $mysqli->query($sql);
if (!$res) { echo "QUERY_ERROR: " . $mysqli->error . PHP_EOL; exit(1); }
$out = [];
while ($row = $res->fetch_assoc()) { 
    $out[] = [
        'id' => $row['id'],
        'code' => $row['code'], 
        'description' => $row['description'],
        'department_name' => $row['department_name'],
        'office_name' => $row['office_name']
    ]; 
}
echo "Forms that should appear on public page:\n";
echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
$mysqli->close();
