<?php
require __DIR__ . '/../vendor/autoload.php';
$m = new \App\Models\ConfigurationModel();
$c = $m->where('config_key','auto_create_schedule_on_approval')->first();
if ($c) {
    echo "Found config:\n";
    print_r($c);
} else {
    echo "Config not found\n";
}
