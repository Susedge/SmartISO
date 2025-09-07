<?php
$mysqli = new mysqli('localhost','root','','smartiso',3306);
if ($mysqli->connect_error) { echo "CONNECT_ERROR: " . $mysqli->connect_error . PHP_EOL; exit(1); }
$res = $mysqli->query('SELECT * FROM department_office LIMIT 50');
if (!$res) { echo "QUERY_ERROR: " . $mysqli->error . PHP_EOL; exit(1); }
$out = [];
while ($r = $res->fetch_assoc()) { $out[] = $r; }
echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
