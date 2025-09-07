<?php
$host = 'localhost';
$db = 'smartiso';
$user = 'root';
$pass = '';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

echo "Testing Forms Query Logic...\n\n";

// First, let's check what columns exist
echo "Checking table structures...\n";
$result = $mysqli->query("SHOW COLUMNS FROM forms");
echo "Forms columns: ";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " ";
}
echo "\n";

$result = $mysqli->query("SHOW COLUMNS FROM departments");
echo "Departments columns: ";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " ";
}
echo "\n";

$result = $mysqli->query("SHOW COLUMNS FROM offices");
echo "Offices columns: ";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " ";
}
echo "\n\n";

// Test 1: Basic forms query (simplified)
$sql = "SELECT forms.*, 
               departments.description as dept_name, departments.code as dept_code,
               offices.description as office_name, offices.code as office_code
        FROM forms 
        LEFT JOIN departments ON forms.department_id = departments.id 
        LEFT JOIN offices ON forms.office_id = offices.id";

echo "1. All active forms:\n";
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['description']} (Dept: {$row['dept_name']}, Office: {$row['office_name']})\n";
}

// Test 2: Filter by department (what should happen with GET parameter)
$department_filter = 1;
$sql_filtered = $sql . " WHERE forms.department_id = ?";
$stmt = $mysqli->prepare($sql_filtered);
$stmt->bind_param('i', $department_filter);
$stmt->execute();
$result = $stmt->get_result();

echo "\n2. Filtered by department_id = 1:\n";
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['description']} (Dept: {$row['dept_name']}, Office: {$row['office_name']})\n";
}

// Test 3: Filter by non-existent department
$department_filter = 99;
$stmt = $mysqli->prepare($sql_filtered);
$stmt->bind_param('i', $department_filter);
$stmt->execute();
$result = $stmt->get_result();

echo "\n3. Filtered by department_id = 99 (should be empty):\n";
$count = 0;
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['description']} (Dept: {$row['dept_name']}, Office: {$row['office_name']})\n";
    $count++;
}
if ($count === 0) {
    echo "  (No forms found - correct!)\n";
}

$mysqli->close();
?>
