<?php
// This script shows SQL to create a pivot table department_office and backfill from offices.department_id.
// It will not execute anything by default. To execute, pass --apply=1
$apply = false;
foreach ($argv as $arg) {
    if (preg_match('/^--apply=(.*)$/', $arg, $m)) { $apply = (int)$m[1] === 1; }
}
$sqlCreate = "CREATE TABLE IF NOT EXISTS department_office (\n  department_id INT NOT NULL,\n  office_id INT NOT NULL,\n  PRIMARY KEY(department_id, office_id),\n  KEY idx_office (office_id)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$sqlBackfill = "INSERT IGNORE INTO department_office (department_id, office_id) SELECT department_id, id FROM offices WHERE department_id IS NOT NULL;";

echo "-- DDL to create pivot table:\n" . $sqlCreate . "\n\n-- Backfill DML:\n" . $sqlBackfill . "\n\n";
if ($apply) {
    echo "-- Running SQL... (this will execute against the local DB)\n";
    $mysqli = new mysqli('localhost', 'root', '', 'smartiso', 3306);
    if ($mysqli->connect_error) { echo "CONNECT_ERROR: " . $mysqli->connect_error . PHP_EOL; exit(1); }
    if (!$mysqli->query($sqlCreate)) { echo "CREATE_ERROR: " . $mysqli->error . PHP_EOL; }
    if (!$mysqli->query($sqlBackfill)) { echo "BACKFILL_ERROR: " . $mysqli->error . PHP_EOL; }
    echo "-- Done\n";
    $mysqli->close();
} else {
    echo "To apply these changes run: php create_department_office_pivot.php --apply=1\n";
}
