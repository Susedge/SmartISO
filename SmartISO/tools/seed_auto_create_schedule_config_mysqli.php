<?php
/**
 * Simple mysqli seeder for 'auto_create_schedule_on_submit' configuration row.
 * Run with: php tools/seed_auto_create_schedule_config_mysqli.php
 */
$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';
$dbname = 'smartiso';

$mysqli = new mysqli($host, $user, $pass, $dbname, $port);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error . "\n";
    exit(1);
}

// Ensure table exists
$res = $mysqli->query("SHOW TABLES LIKE 'configurations'");
if (!$res || $res->num_rows === 0) {
    echo "Table 'configurations' does not exist in database '{$dbname}'.\n";
    exit(1);
}

$key = 'auto_create_schedule_on_submit';
$stmt = $mysqli->prepare("SELECT id, config_value FROM configurations WHERE config_key = ? LIMIT 1");
$stmt->bind_param('s', $key);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if ($row) {
    echo "Config row already exists: {$key} => {$row['config_value']}\n";
    exit(0);
}

// Insert with config_description column name if present, otherwise try description
$columnsRes = $mysqli->query("SHOW COLUMNS FROM configurations");
$cols = [];
while ($c = $columnsRes->fetch_assoc()) { $cols[] = $c['Field']; }

$now = date('Y-m-d H:i:s');
if (in_array('config_description', $cols)) {
    $sql = "INSERT INTO configurations (config_key, config_value, config_type, config_description, created_at) VALUES (?, '0', 'boolean', ?, ? )";
    $desc = 'Automatically create schedule row when a submission is created';
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('sss', $key, $desc, $now);
} elseif (in_array('description', $cols)) {
    $sql = "INSERT INTO configurations (config_key, config_value, config_type, description, created_at) VALUES (?, '0', 'boolean', ?, ? )";
    $desc = 'Automatically create schedule row when a submission is created';
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('sss', $key, $desc, $now);
} else {
    // Fallback: insert minimal columns
    $sql = "INSERT INTO configurations (config_key, config_value, config_type, created_at) VALUES (?, '0', 'boolean', ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ss', $key, $now);
}

if (!$stmt) {
    echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error . "\n";
    exit(1);
}

if ($stmt->execute()) {
    echo "Inserted configuration row for {$key}\n";
    exit(0);
} else {
    echo "Failed to insert configuration row: (" . $stmt->errno . ") " . $stmt->error . "\n";
    exit(1);
}
