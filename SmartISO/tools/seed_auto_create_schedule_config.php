<?php
/**
 * One-off script to ensure 'auto_create_schedule_on_submit' config row exists.
 * Run via CLI: php tools/seed_auto_create_schedule_config.php
 */
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap minimal CodeIgniter environment
// The app's bootstrap expects to be run from the public index; this script uses the models directly.
$db = \Config\Database::connect();
$configurations = $db->table('configurations');

$key = 'auto_create_schedule_on_submit';
$existing = $configurations->where('config_key', $key)->get()->getRowArray();
if ($existing) {
    echo "Config row already exists: {$key} => {$existing['config_value']}\n";
    exit(0);
}

$insert = [
    'config_key' => $key,
    'config_value' => '0',
    'config_type' => 'boolean',
    'description' => 'Automatically create schedule row when a submission is created',
    'created_at' => date('Y-m-d H:i:s')
];
$db->table('configurations')->insert($insert);
if ($db->affectedRows() > 0) {
    echo "Inserted configuration row for {$key}\n";
} else {
    echo "Failed to insert configuration row for {$key}\n";
}
