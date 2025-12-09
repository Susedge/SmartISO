<?php
$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Forms table columns:\n";
$result = $pdo->query('SHOW COLUMNS FROM forms');
foreach($result->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "  " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n\nSchedules table columns:\n";
$result = $pdo->query('SHOW COLUMNS FROM schedules');
foreach($result->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "  " . $col['Field'] . " (" . $col['Type'] . ")\n";
}
