<?php
// tools/list_schedules.php
// Quick helper to list recent schedules using project DB config
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'smartiso';
$port = 3306;

$mysqli = @new mysqli($host, $user, $pass, $db, $port);
if ($mysqli->connect_errno) {
    echo "DB connect error: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}

$sql = "SELECT id, submission_id, scheduled_date, scheduled_time, assigned_staff_id, status, created_at FROM schedules ORDER BY id DESC LIMIT 10";
$res = $mysqli->query($sql);
if (!$res) {
    echo "Query error: " . $mysqli->error . PHP_EOL;
    exit(1);
}

echo "id | submission_id | scheduled_date | scheduled_time | assigned_staff_id | status | created_at" . PHP_EOL;
$rows = 0;
while ($row = $res->fetch_assoc()) {
    echo sprintf('%d | %s | %s | %s | %s | %s | %s',
        $row['id'],
        $row['submission_id'] ?? '',
        $row['scheduled_date'] ?? '',
        $row['scheduled_time'] ?? '',
        $row['assigned_staff_id'] ?? '',
        $row['status'] ?? '',
        $row['created_at'] ?? '') . PHP_EOL;
    $rows++;
}
if ($rows === 0) {
    echo "No schedule rows found." . PHP_EOL;
}

$mysqli->close();
