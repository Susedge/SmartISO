<?php
// tools/upsert_config.php
require __DIR__ . '/../vendor/autoload.php';

use App\Models\ConfigurationModel;

$model = new ConfigurationModel();
$key = 'auto_create_schedule_on_approval';
$value = 1;
$description = 'Auto-create a pending schedule when a submission is approved and assigned to service staff';
$type = 'boolean';

$res = $model->setConfig($key, $value, $description, $type);
if ($res) {
    echo "Config '$key' set successfully.\n";
} else {
    echo "Failed to set config '$key'\n";
}
