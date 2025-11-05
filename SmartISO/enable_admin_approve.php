<?php
// Enable admin_can_approve
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Update the value to 1 (enabled)
$stmt = $mysqli->prepare("UPDATE configurations SET config_value = '1', updated_at = NOW() WHERE config_key = 'admin_can_approve'");
$stmt->execute();

echo "admin_can_approve has been ENABLED\n";
echo "Affected rows: " . $mysqli->affected_rows . "\n\n";

// Verify
$result = $mysqli->query("SELECT config_key, config_value, config_type FROM configurations WHERE config_key = 'admin_can_approve'");
if ($row = $result->fetch_assoc()) {
    echo "Current value:\n";
    echo "  Key: " . $row['config_key'] . "\n";
    echo "  Value: " . $row['config_value'] . "\n";
    echo "  Status: " . ($row['config_value'] == '1' ? 'ENABLED ✓' : 'DISABLED ✗') . "\n";
}

$mysqli->close();

echo "\nYou can now access the Pending Approval page as a global admin.\n";
