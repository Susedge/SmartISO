<?php
$host = 'localhost';
$db = 'smartiso';
$user = 'root';
$pass = '';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

echo "Debugging Office Filtering Issue...\n\n";

// Check what office IDs exist and their names
echo "1. All offices:\n";
$result = $mysqli->query("SELECT id, code, description, department_id FROM offices ORDER BY description");
while ($row = $result->fetch_assoc()) {
    echo "  - ID: {$row['id']}, Code: {$row['code']}, Name: {$row['description']}, Dept: {$row['department_id']}\n";
}

// Check the current form and its office
echo "\n2. Current form details:\n";
$result = $mysqli->query("SELECT f.*, d.description as dept_name, o.description as office_name FROM forms f LEFT JOIN departments d ON f.department_id = d.id LEFT JOIN offices o ON f.office_id = o.id");
while ($row = $result->fetch_assoc()) {
    echo "  - Form: {$row['description']}\n";
    echo "    Department ID: {$row['department_id']} ({$row['dept_name']})\n";
    echo "    Office ID: {$row['office_id']} ({$row['office_name']})\n";
}

// Test what happens when filtering by IT office (likely ID 2)
echo "\n3. Testing filter by Information Technology Office (ID 2):\n";
$sql = "SELECT f.*, d.description AS department_name, o.description AS office_name
        FROM forms f 
        LEFT JOIN departments d ON d.id = f.department_id 
        LEFT JOIN offices o ON o.id = f.office_id
        WHERE f.office_id = 2";
$result = $mysqli->query($sql);
$count = 0;
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['description']} (Dept: {$row['department_name']}, Office: {$row['office_name']})\n";
    $count++;
}
if ($count === 0) {
    echo "  (No forms found - this is correct if no forms belong to IT office)\n";
}

$mysqli->close();
?>
