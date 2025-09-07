<?php
// Test query with department filter = 12 (Administration)
$mysqli = new mysqli('localhost', 'root', '', 'smartiso', 3306);
if ($mysqli->connect_error) { echo "CONNECT_ERROR: " . $mysqli->connect_error . PHP_EOL; exit(1); }

$selectedDepartment = 12; // Administration

$sql = "SELECT f.*, COALESCE(d1.description, d3.description) AS department_name, o.description AS office_name
FROM forms f
LEFT JOIN departments d1 ON d1.id = f.department_id
LEFT JOIN offices o ON o.id = f.office_id
LEFT JOIN department_office do ON do.office_id = o.id
LEFT JOIN departments d3 ON d3.id = do.department_id
WHERE (f.department_id = ? OR do.department_id = ?)
ORDER BY f.description ASC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $selectedDepartment, $selectedDepartment);
$stmt->execute();
$res = $stmt->get_result();

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
echo "Forms when filtering by Administration department:\n";
echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
$mysqli->close();
