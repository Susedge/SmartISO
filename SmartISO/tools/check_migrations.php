<?php
require __DIR__ . '/../vendor/autoload.php';
// Minimal bootstrap for database
$db = Config\Database::connect();
$result = $db->query('SELECT * FROM migrations')->getResultArray();
print_r($result);
