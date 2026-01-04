<?php
// Test script to check automatic scheduling configs
require __DIR__ . '/SmartISO/preload.php';

$db = \Config\Database::connect();
$query = $db->query("SELECT * FROM configurations WHERE config_key LIKE '%schedule%'");
$results = $query->getResultArray();

echo "\n=== Automatic Scheduling Configurations ===\n\n";

if (empty($results)) {
    echo "❌ No scheduling configs found!\n";
} else {
    foreach ($results as $config) {
        echo "✅ " . $config['config_key'] . "\n";
        echo "   Value: " . $config['config_value'] . "\n";
        echo "   Type: " . $config['config_type'] . "\n";
        echo "   Description: " . $config['config_description'] . "\n\n";
    }
}

// Check if they'll display in the view
$allConfigs = $db->query("SELECT * FROM configurations")->getResultArray();
echo "Total configurations: " . count($allConfigs) . "\n";
?>
