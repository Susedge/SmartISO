<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'smartiso';
$m = new mysqli($host, $user, $pass, $db);
if ($m->connect_errno) { echo "CONNECT_ERROR: {$m->connect_errno} {$m->connect_error}\n"; exit(1); }

echo "Checking department_office table...\n";
$res = $m->query("SHOW TABLES LIKE 'department_office'");
if (!$res) { echo "SHOW TABLES ERROR: {$m->error}\n"; $m->close(); exit(1); }
if ($res->num_rows == 0) { echo "department_office not found, nothing to do.\n"; $m->close(); exit(0); }

// Create backup
echo "Creating backup table department_office_backup...\n";
if (!$m->query("CREATE TABLE IF NOT EXISTS department_office_backup LIKE department_office")) {
    echo "CREATE BACKUP ERROR: {$m->error}\n"; $m->close(); exit(1);
}

// Copy rows
if (!$m->query("INSERT INTO department_office_backup SELECT * FROM department_office")) {
    echo "INSERT BACKUP ERROR: {$m->error}\n"; $m->close(); exit(1);
}

$origCount = $m->query("SELECT COUNT(*) AS cnt FROM department_office")->fetch_assoc()['cnt'];
$backupCount = $m->query("SELECT COUNT(*) AS cnt FROM department_office_backup")->fetch_assoc()['cnt'];

echo "Original rows: {$origCount}, backup rows: {$backupCount}\n";

if ($backupCount >= $origCount) {
    echo "Dropping original department_office...\n";
    if (!$m->query("DROP TABLE IF EXISTS department_office")) {
        echo "DROP ERROR: {$m->error}\n"; $m->close(); exit(1);
    }
    echo "Dropped department_office. Backup preserved as department_office_backup.\n";
} else {
    echo "Backup incomplete, aborting drop.\n";
}

$m->close();
