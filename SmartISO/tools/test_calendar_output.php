<?php
require_once __DIR__ . '/../vendor/autoload.php';
// Minimal bootstrap for CodeIgniter components used
// NOTE: Running full CI controller outside HTTP context can be fragile; we'll instead directly use models

$host = 'localhost';
$db = 'smartiso';
$user = 'root';
$pass = '';
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) { die('DB conn fail: ' . $mysqli->connect_error); }

// Find submissions for a sample requestor (pick user_id 2 if exists)
$reqUserId = 2;
$res = $mysqli->query("SELECT id, form_id, panel_name, status, created_at FROM form_submissions WHERE submitted_by = {$reqUserId} ORDER BY created_at DESC");
$subs = [];
while ($r = $res->fetch_assoc()) { $subs[] = $r; }

echo "Found " . count($subs) . " submissions for user {$reqUserId}\n";
foreach ($subs as $s) {
    echo " - ID {$s['id']} status={$s['status']} created={$s['created_at']}\n";
}

// Now check schedules for these submissions
$ids = array_map(function($s){ return (int)$s['id']; }, $subs);
if (!empty($ids)) {
    $in = implode(',', $ids);
    $r = $mysqli->query("SELECT * FROM schedules WHERE submission_id IN ({$in})");
    $count = 0;
    while ($row = $r->fetch_assoc()) { $count++; echo "Schedule: id={$row['id']} submission_id={$row['submission_id']} date={$row['scheduled_date']} time={$row['scheduled_time']}\n"; }
    if ($count === 0) echo "No schedules found for these submissions\n";
}

$mysqli->close();
?>
