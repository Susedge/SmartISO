<?php
// Direct mysqli check to avoid needing CodeIgniter bootstrap in CLI
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'smartiso';

$m = @new mysqli($host, $user, $pass, $db);
if ($m->connect_errno) {
    echo "connect_error: ({$m->connect_errno}) {$m->connect_error}\n";
    exit(1);
}

$res = $m->query("SHOW TABLES LIKE 'department_office_backup'");
if (!$res) {
    echo "query_error: {$m->error}\n";
    $m->close();
    exit(1);
}

if ($res->num_rows == 0) {
    echo "department_office_backup NOT FOUND\n";
} else {
    echo "department_office_backup EXISTS\n";
    $c = $m->query("SELECT COUNT(*) AS cnt FROM department_office_backup");
    if ($c) {
        $row = $c->fetch_assoc();
        echo "rows: {$row['cnt']}\n";
    } else {
        echo "count_query_error: {$m->error}\n";
    }
}

$m->close();
