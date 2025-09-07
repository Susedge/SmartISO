<?php
$db=new mysqli('localhost','root','','smartiso');
if ($db->connect_error) die('conn');
$schedDate = date('Y-m-d');
$res = $db->query("INSERT INTO schedules (submission_id, scheduled_date, scheduled_time, duration_minutes, assigned_staff_id, location, notes, status, created_at, updated_at) VALUES (18, '{$schedDate}', '09:00:00', 60, NULL, '', '', 'pending', NOW(), NOW())");
if ($res) echo "Inserted schedule for submission 18\n"; else echo 'Insert failed: ' . $db->error . "\n";
$db->close();
?>
