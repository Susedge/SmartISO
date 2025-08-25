<?php
// Safe sync script: set completed=1 where status='completed' but completed is not 1
// Creates a local backup file with affected IDs before updating.

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'smartiso';
$port = 3306;

$mysqli = new mysqli($host, $user, $pass, $db, $port);
if ($mysqli->connect_error) {
    fwrite(STDERR, "CONNECTION ERROR: " . $mysqli->connect_error . PHP_EOL);
    exit(2);
}

// Find affected rows
$res = $mysqli->query("SELECT id FROM form_submissions WHERE status = 'completed' AND (completed IS NULL OR completed = 0)");
if ($res === false) {
    fwrite(STDERR, "SELECT ERROR: " . $mysqli->error . PHP_EOL);
    $mysqli->close();
    exit(3);
}

$ids = [];
while ($row = $res->fetch_assoc()) {
    $ids[] = $row['id'];
}

$count = count($ids);
echo "Found $count submissions to update\n";

if ($count === 0) {
    echo "Nothing to do. Exiting.\n";
    $mysqli->close();
    exit(0);
}

// Write backup file
$backupDir = __DIR__ . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
$backupFile = $backupDir . DIRECTORY_SEPARATOR . 'completed_sync_ids_' . date('Ymd_His') . '.txt';
file_put_contents($backupFile, implode("\n", $ids));
echo "Wrote backup IDs to: $backupFile\n";

// Build safe WHERE clause using IDs
$placeholders = implode(',', array_map(function($v){ return intval($v); }, $ids));
$sql = "UPDATE form_submissions SET completed = 1 WHERE id IN ($placeholders)";

$updated = $mysqli->query($sql);
if ($updated === false) {
    fwrite(STDERR, "UPDATE ERROR: " . $mysqli->error . PHP_EOL);
    $mysqli->close();
    exit(4);
}

echo "Updated rows: " . $mysqli->affected_rows . "\n";
$mysqli->close();
exit(0);
