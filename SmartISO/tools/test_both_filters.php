<?php
// Test with both department=12 (Administration) AND office=2 (Information Technology Office)
$mysqli = new mysqli('localhost', 'root', '', 'smartiso', 3306);
if ($mysqli->connect_error) { echo "CONNECT_ERROR: " . $mysqli->connect_error . PHP_EOL; exit(1); }

$selectedDepartment = 12; // Administration  
$selectedOffice = 2; // Information Technology Office

$sql = "SELECT f.*, COALESCE(d1.description, d3.description) AS department_name, o.description AS office_name
FROM forms f
LEFT JOIN departments d1 ON d1.id = f.department_id
LEFT JOIN offices o ON o.id = f.office_id
LEFT JOIN department_office do ON do.office_id = o.id
LEFT JOIN departments d3 ON d3.id = do.department_id
WHERE (f.department_id = ? OR do.department_id = ?) AND f.office_id = ?
ORDER BY f.description ASC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('iii', $selectedDepartment, $selectedDepartment, $selectedOffice);
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
echo "Forms when filtering by Administration department AND Information Technology Office:\n";
if (empty($out)) {
    echo "No forms found (this explains why CRSRF doesn't appear)\n";
} else {
    echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
}

// Show what office CRSRF is actually assigned to
$res2 = $mysqli->query("SELECT f.code, o.description as office_name FROM forms f LEFT JOIN offices o ON o.id = f.office_id WHERE f.code = 'CRSRF'");
$row2 = $res2->fetch_assoc();
echo "\nCRSRF is actually assigned to office: " . ($row2['office_name'] ?? 'None') . "\n";
$mysqli->close();
