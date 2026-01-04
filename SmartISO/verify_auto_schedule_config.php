<?php
require_once __DIR__ . '/vendor/autoload.php';

$pathsConfig = APPPATH . '../app/Config/Paths.php';
require realpath($pathsConfig) ?: $pathsConfig;

$paths = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$app = require realpath($bootstrap) ?: $bootstrap;

$db = \Config\Database::connect();
$configs = $db->table('configurations')
    ->whereIn('config_key', ['auto_create_schedule_on_submit', 'auto_create_schedule_on_approval'])
    ->get()
    ->getResultArray();

echo "Automatic Scheduling Configurations:\n";
echo str_repeat("=", 50) . "\n";

foreach ($configs as $c) {
    echo "Key: " . $c['config_key'] . "\n";
    echo "Value: " . $c['config_value'] . "\n";
    echo "Type: " . $c['config_type'] . "\n";
    echo "Description: " . $c['config_description'] . "\n";
    echo str_repeat("-", 50) . "\n";
}

if (empty($configs)) {
    echo "No configurations found!\n";
} else {
    echo "\n✓ " . count($configs) . " configuration(s) found!\n";
}
