<?php
// tools/sql_upsert_config.php - upsert config row using mysqli to avoid CI bootstrap
$host='localhost'; $user='root'; $pass=''; $db='smartiso'; $port=3306;
$mysqli = new mysqli($host,$user,$pass,$db,$port);
if ($mysqli->connect_errno) { echo "DB connect error: " . $mysqli->connect_error . PHP_EOL; exit(1); }

$key = 'auto_create_schedule_on_approval';
$val = '1';
${'desc'} = 'Auto-create a pending schedule when a submission is approved and assigned to service staff';
$type = 'boolean';

// Check exists
$stmt = $mysqli->prepare('SELECT id FROM configurations WHERE config_key = ? LIMIT 1');
$stmt->bind_param('s', $key);
$stmt->execute();
$stmt->bind_result($id);
$exists = $stmt->fetch();
$stmt->close();

if ($exists && $id) {
    $upd = $mysqli->prepare('UPDATE configurations SET config_value = ?, config_description = ?, config_type = ? WHERE id = ?');
    $upd->bind_param('sssi', $val, $desc, $type, $id);
    $upd->execute();
    echo "Config updated (id=$id)\n";
    $upd->close();
} else {
    $ins = $mysqli->prepare('INSERT INTO configurations (config_key, config_value, config_description, config_type, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
    $ins->bind_param('ssss', $key, $val, $desc, $type);
    $ins->execute();
    echo "Config inserted (id=" . $ins->insert_id . ")\n";
    $ins->close();
}

$mysqli->close();
