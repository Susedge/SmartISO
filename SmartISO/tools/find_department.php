<?php
// Quick lookup for departments matching "finance"
$mysqli = new mysqli('localhost', 'root', '', 'smartiso', 3306);
if ($mysqli->connect_error) {
    echo "CONNECT_ERROR: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}
$term = $mysqli->real_escape_string('%finance%');
$sql = "SELECT id, code, description FROM departments WHERE description LIKE '$term' OR code LIKE '$term' ORDER BY id LIMIT 100";
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
