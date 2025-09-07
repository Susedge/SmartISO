<?php
// Test filtering functionality
$host='localhost'; $user='root'; $pass=''; $db='smartiso'; $port=3306;
$mysqli = new mysqli($host,$user,$pass,$db,$port);
if ($mysqli->connect_errno) { echo "DB connect error: " . $mysqli->connect_error . PHP_EOL; exit(1); }

echo "Testing Forms Filtering...\n\n";

// Test 1: Get all forms
echo "1. All forms:\n";
$sql = "SELECT f.id, f.code, f.description, f.department_id, f.office_id, 
        d.description AS department_name, o.description AS office_name 
        FROM forms f 
        LEFT JOIN departments d ON d.id = f.department_id 
        LEFT JOIN offices o ON o.id = f.office_id 
        ORDER BY f.description ASC";
$result = $mysqli->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo sprintf("  %s (%s) - Dept: %s (ID: %s), Office: %s (ID: %s)\n", 
            $row['description'], $row['code'], 
            $row['department_name'] ?: 'NULL', $row['department_id'] ?: 'NULL',
            $row['office_name'] ?: 'NULL', $row['office_id'] ?: 'NULL'
        );
    }
} else {
    echo "  Error: " . $mysqli->error . "\n";
}

echo "\n2. Available departments:\n";
$result = $mysqli->query("SELECT id, code, description FROM departments ORDER BY description");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo sprintf("  %s: %s (ID: %s)\n", $row['code'], $row['description'], $row['id']);
    }
}

echo "\n3. Available offices:\n";
$result = $mysqli->query("SELECT id, code, description, department_id FROM offices ORDER BY description");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo sprintf("  %s: %s (ID: %s, Dept: %s)\n", $row['code'], $row['description'], $row['id'], $row['department_id'] ?: 'NULL');
    }
}

echo "\n4. Test filtering by department ID = 1:\n";
$sql = "SELECT f.id, f.code, f.description, d.description AS department_name 
        FROM forms f 
        LEFT JOIN departments d ON d.id = f.department_id 
        WHERE f.department_id = 1
        ORDER BY f.description ASC";
$result = $mysqli->query($sql);
if ($result) {
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        echo sprintf("  %s (%s) - Dept: %s\n", $row['description'], $row['code'], $row['department_name']);
        $count++;
    }
    echo "  Total: $count forms\n";
} else {
    echo "  Error: " . $mysqli->error . "\n";
}

$mysqli->close();
