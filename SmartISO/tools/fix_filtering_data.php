<?php
// Fix the filtering data issues
$host='localhost'; $user='root'; $pass=''; $db='smartiso'; $port=3306;
$mysqli = new mysqli($host,$user,$pass,$db,$port);
if ($mysqli->connect_errno) { echo "DB connect error: " . $mysqli->connect_error . PHP_EOL; exit(1); }

echo "Fixing data issues for filtering...\n\n";

// First, let's see what departments exist
echo "Current departments:\n";
$result = $mysqli->query("SELECT * FROM departments");
while ($row = $result->fetch_assoc()) {
    echo sprintf("  ID: %s, Code: %s, Description: %s\n", $row['id'], $row['code'], $row['description']);
}

// Check current forms
echo "\nCurrent forms:\n";
$result = $mysqli->query("SELECT * FROM forms");
while ($row = $result->fetch_assoc()) {
    echo sprintf("  ID: %s, Code: %s, Description: %s, Dept ID: %s, Office ID: %s\n", 
        $row['id'], $row['code'], $row['description'], $row['department_id'] ?: 'NULL', $row['office_id'] ?: 'NULL');
}

// Check current offices
echo "\nCurrent offices:\n";
$result = $mysqli->query("SELECT * FROM offices");
while ($row = $result->fetch_assoc()) {
    echo sprintf("  ID: %s, Code: %s, Description: %s, Dept ID: %s\n", 
        $row['id'], $row['code'], $row['description'], $row['department_id'] ?: 'NULL');
}

// Fix 1: Update the form to use department ID 1 (Agriculture)
echo "\nFix 1: Updating form department_id from 8 to 1...\n";
$result = $mysqli->query("UPDATE forms SET department_id = 1 WHERE code = 'CRSRF'");
if ($result) {
    echo "  ✓ Form department updated\n";
} else {
    echo "  ✗ Error: " . $mysqli->error . "\n";
}

// Fix 2: Associate offices with departments
echo "\nFix 2: Associating offices with departments...\n";
$updates = [
    ['office_id' => 1, 'dept_id' => 1, 'name' => 'Administration Office'],
    ['office_id' => 2, 'dept_id' => 1, 'name' => 'Information Technology Office'],
    ['office_id' => 3, 'dept_id' => 1, 'name' => 'Human Resources Office'],
    ['office_id' => 4, 'dept_id' => 1, 'name' => 'Finance Office']
];

foreach ($updates as $update) {
    $result = $mysqli->query("UPDATE offices SET department_id = {$update['dept_id']} WHERE id = {$update['office_id']}");
    if ($result) {
        echo "  ✓ {$update['name']} associated with department {$update['dept_id']}\n";
    } else {
        echo "  ✗ Error updating {$update['name']}: " . $mysqli->error . "\n";
    }
}

// Verify the fixes
echo "\nVerification - Forms with proper associations:\n";
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
}

$mysqli->close();
