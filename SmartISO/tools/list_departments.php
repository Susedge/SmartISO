<?php
// Dump all departments
$mysqli = new mysqli('localhost', 'root', '', 'smartiso', 3306);
if ($mysqli->connect_error) {
    echo "CONNECT_ERROR: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}
$sql = "SELECT id, code, description FROM departments ORDER BY id";
$res = $mysqli->query($sql);
if (!$res) {
    echo "QUERY_ERROR: " . $mysqli->error . PHP_EOL;
    exit(1);
}
$out = [];
while ($row = $res->fetch_assoc()) {
    $out[] = $row;
}
echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
$mysqli->close();
