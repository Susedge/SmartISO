<?php
// Quick DB state inspector using mysqli
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'smartiso';

$mysqli = @new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    echo "connect_error: ({$mysqli->connect_errno}) {$mysqli->connect_error}\n";
    exit(1);
}

function tableExists($m, $name) {
    $res = $m->query("SHOW TABLES LIKE '" . $m->real_escape_string($name) . "'");
    return ($res && $res->num_rows > 0);
}

$tables = ['department_office', 'department_office_backup', 'offices', 'migrations'];
foreach ($tables as $t) {
    echo str_pad($t, 30) . ': ' . (tableExists($mysqli, $t) ? 'YES' : 'NO') . "\n";
}

if (tableExists($mysqli, 'offices')) {
    $res = $mysqli->query("SHOW COLUMNS FROM offices LIKE 'department_id'");
    echo "offices.department_id column: " . ($res && $res->num_rows ? 'YES' : 'NO') . "\n";
    $r = $mysqli->query("SELECT COUNT(*) AS cnt, SUM(department_id IS NOT NULL) AS have_dept FROM offices")->fetch_assoc();
    echo "offices rows: {$r['cnt']}, with department_id set: {$r['have_dept']}\n";
    $sample = $mysqli->query("SELECT id, code, description, department_id FROM offices ORDER BY id DESC LIMIT 10");
    echo "--- sample offices ---\n";
    while ($row = $sample->fetch_assoc()) {
        echo "id={$row['id']} code={$row['code']} dept_id=" . ($row['department_id'] ?? 'NULL') . " desc={$row['description']}\n";
    }
}

if (tableExists($mysqli, 'department_office')) {
    $r2 = $mysqli->query("SELECT COUNT(*) AS cnt FROM department_office")->fetch_assoc();
    echo "department_office rows: {$r2['cnt']}\n";
}

if (tableExists($mysqli, 'department_office_backup')) {
    $r3 = $mysqli->query("SELECT COUNT(*) AS cnt FROM department_office_backup")->fetch_assoc();
    echo "department_office_backup rows: {$r3['cnt']}\n";
}

if (tableExists($mysqli, 'migrations')) {
    $rm = $mysqli->query("SELECT version FROM migrations ORDER BY id DESC LIMIT 20");
    echo "--- recent migrations ---\n";
    while ($mrow = $rm->fetch_assoc()) echo $mrow['version'] . "\n";
}

$mysqli->close();
