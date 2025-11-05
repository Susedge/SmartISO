<?php
// Check approver user types
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "=== Checking Approver User Types ===\n\n";

$result = $mysqli->query("SELECT id, full_name, email, user_type FROM users WHERE user_type LIKE '%approv%' ORDER BY user_type, full_name");

if ($result && $result->num_rows > 0) {
    echo "Users with 'approv' in user_type:\n";
    echo str_repeat('-', 80) . "\n";
    printf("%-5s %-30s %-35s %-20s\n", "ID", "Name", "Email", "User Type");
    echo str_repeat('-', 80) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        printf("%-5s %-30s %-35s %-20s\n", 
            $row['id'], 
            substr($row['full_name'], 0, 30), 
            substr($row['email'], 0, 35),
            $row['user_type']
        );
    }
} else {
    echo "No approver users found.\n";
}

echo "\n\n=== Valid User Types for Approval ===\n";
echo "According to canUserApprove() method:\n";
echo "  - approving_authority\n";
echo "  - department_admin\n";
echo "  - admin (if admin_can_approve is enabled)\n";
echo "  - superuser (if admin_can_approve is enabled)\n";

echo "\n\n=== All Unique User Types in Database ===\n";
$result = $mysqli->query("SELECT DISTINCT user_type, COUNT(*) as count FROM users GROUP BY user_type ORDER BY user_type");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['user_type'] . " (" . $row['count'] . " users)\n";
    }
}

$mysqli->close();
