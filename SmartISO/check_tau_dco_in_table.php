<?php
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "=== Checking TAU-DCO User in Database ===\n\n";

// Check if tau_dco user exists
$result = $mysqli->query("SELECT id, username, full_name, email, user_type, active, department_id FROM users WHERE user_type='tau_dco' OR id=14");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Found user:\n";
        echo "  ID: " . $row['id'] . "\n";
        echo "  Username: " . $row['username'] . "\n";
        echo "  Full Name: " . $row['full_name'] . "\n";
        echo "  Email: " . $row['email'] . "\n";
        echo "  User Type: " . $row['user_type'] . "\n";
        echo "  Active: " . ($row['active'] ? 'Yes' : 'No') . "\n";
        echo "  Department ID: " . ($row['department_id'] ?? 'NULL') . "\n\n";
    }
} else {
    echo "❌ No TAU-DCO user found!\n\n";
}

// Check all users to see ID range
echo "=== All User IDs and Types ===\n";
$result = $mysqli->query("SELECT id, username, user_type, active FROM users ORDER BY id");
while ($row = $result->fetch_assoc()) {
    echo "ID {$row['id']}: {$row['username']} ({$row['user_type']}) - " . ($row['active'] ? 'Active' : 'Inactive') . "\n";
}

$mysqli->close();
