<?php
// Simple CLI database backup script for Windows/CLI environments.
// Usage: php backup_database.php

$root = dirname(__DIR__);
$dbConfigPath = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Database.php';
if (!file_exists($dbConfigPath)) {
    echo "Database config file not found: $dbConfigPath\n";
    exit(1);
}

$contents = file_get_contents($dbConfigPath);
if ($contents === false) {
    echo "Failed to read Database.php\n";
    exit(1);
}

// Rudimentary regex extraction (works with standard CodeIgniter Database.php format)
$patterns = [
    'hostname' => "/'hostname'\s*=>\s*'([^']*)'/",
    'username' => "/'username'\s*=>\s*'([^']*)'/",
    'password' => "/'password'\s*=>\s*'([^']*)'/",
    'database' => "/'database'\s*=>\s*'([^']*)'/",
    'port' => "/'port'\s*=>\s*([0-9]+)/"
];

$config = [];
foreach ($patterns as $key => $re) {
    if (preg_match($re, $contents, $m)) {
        $config[$key] = $m[1] ?? null;
    } else {
        $config[$key] = null;
    }
}

$host = $config['hostname'] ?: '127.0.0.1';
$user = $config['username'] ?: 'root';
$pass = $config['password'] ?? '';
$db   = $config['database'] ?: '';
$port = $config['port'] ? (int)$config['port'] : 3306;

if (empty($db)) {
    echo "No database configured in Database.php\n";
    exit(1);
}

$backupDir = $root . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;
if (!is_dir($backupDir)) {
    if (!mkdir($backupDir, 0755, true)) {
        echo "Failed to create backup directory: $backupDir\n";
        exit(1);
    }
}

$filename = 'db_backup_' . date('Ymd_His') . '.sql';
$filepath = $backupDir . $filename;

$mysqli = @new mysqli($host, $user, $pass, $db, $port);
if ($mysqli->connect_errno) {
    echo "MySQL connection failed: ({$mysqli->connect_errno}) {$mysqli->connect_error}\n";
    exit(1);
}

$fp = fopen($filepath, 'w');
if (!$fp) {
    echo "Failed to open file for writing: $filepath\n";
    exit(1);
}

fwrite($fp, "-- Database Backup\n-- Generated: " . date('c') . "\n\n");

$tablesRes = $mysqli->query('SHOW TABLES');
if (!$tablesRes) {
    echo "Failed to list tables: " . $mysqli->error . "\n";
    fclose($fp);
    exit(1);
}

$tables = [];
while ($r = $tablesRes->fetch_array(MYSQLI_NUM)) { $tables[] = $r[0]; }

foreach ($tables as $table) {
    fwrite($fp, "--\n-- Structure for table `{$table}`\n--\n\n");
    fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");

    $createRes = $mysqli->query("SHOW CREATE TABLE `{$table}`");
    if ($createRes) {
        $createRow = $createRes->fetch_assoc();
        $createSql = $createRow['Create Table'] ?? array_values($createRow)[1] ?? '';
        fwrite($fp, $createSql . ";\n\n");
    }

    // Data
    $rowsRes = $mysqli->query("SELECT * FROM `{$table}`");
    if ($rowsRes && $rowsRes->num_rows > 0) {
        fwrite($fp, "--\n-- Dumping data for table `{$table}`\n--\n\n");
        while ($row = $rowsRes->fetch_assoc()) {
            $cols = array_map(function($c){ return "`" . str_replace('`','``',$c) . "`"; }, array_keys($row));
            $vals = array_map(function($v) use ($mysqli) {
                if ($v === null) return 'NULL';
                return "'" . $mysqli->real_escape_string($v) . "'";
            }, array_values($row));
            fwrite($fp, "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n");
        }
        fwrite($fp, "\n");
    }
    fflush($fp);
}

fclose($fp);
$mysqli->close();

// Rotate backups - keep last 14 files
$files = glob($backupDir . 'db_backup_*.sql');
usort($files, function($a,$b){ return filemtime($b) - filemtime($a); });
$keep = 14;
if (count($files) > $keep) {
    $toDelete = array_slice($files, $keep);
    foreach ($toDelete as $del) { @unlink($del); }
}

echo "Backup completed: $filepath\n";
exit(0);
