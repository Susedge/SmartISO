<?php
// Simple direct DB query
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$result = $mysqli->query("SELECT config_key, config_value, config_type FROM configurations WHERE config_key = 'admin_can_approve'");

if ($result && $row = $result->fetch_assoc()) {
    echo "Current value of admin_can_approve:\n";
    echo "Key: " . $row['config_key'] . "\n";
    echo "Value: " . $row['config_value'] . "\n";
    echo "Type: " . $row['config_type'] . "\n";
    echo "\nTo enable admin approvals, you need to:\n";
    echo "1. Go to Admin > Configurations > System Settings\n";
    echo "2. Click on the 'admin_can_approve' row\n";
    echo "3. Click 'Edit Value' button\n";
    echo "4. Change the value to '1'\n";
    echo "\nCurrent status: " . ($row['config_value'] == '1' ? 'ENABLED' : 'DISABLED') . "\n";
} else {
    echo "admin_can_approve configuration not found!\n";
}

$mysqli->close();
