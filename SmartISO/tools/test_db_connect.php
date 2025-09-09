<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Config/Database.php';
use Config\Database;
$dbConfig = new Database();
$cfg = $dbConfig->default;
$mysqli = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database'], $cfg['port']);
if ($mysqli->connect_errno) {
    echo "CONNECT_ERR:" . $mysqli->connect_error . "\n";
    exit(1);
}
echo "OK: connected as " . $cfg['username'] . " to " . $cfg['database'] . "@" . $cfg['hostname'] . "\n";
$mysqli->close();
