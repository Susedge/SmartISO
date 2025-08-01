<?php
// Simple debug script to test form field retrieval
require_once 'vendor/autoload.php';

// Load CodeIgniter
$app = \Config\Services::codeigniter();
$app->initialize();

// Connect to database
$db = \Config\Database::connect();

// Test 1: Check forms table
echo "=== FORMS TABLE ===\n";
$forms = $db->table('forms')->get()->getResultArray();
foreach ($forms as $form) {
    print_r($form);
}

echo "\n=== DBPANEL TABLE FOR PANEL1 ===\n";
$panelFields = $db->table('dbpanel')
    ->where('panel_name', 'PANEL1')
    ->orderBy('field_order', 'ASC')
    ->get()->getResultArray();

foreach ($panelFields as $field) {
    echo "Field: {$field['field_name']}\n";
    echo "Label: {$field['field_label']}\n";
    echo "Type: {$field['field_type']}\n";
    echo "Role: {$field['field_role']}\n";
    echo "---\n";
}

echo "\n=== TESTING DbpanelModel ===\n";
$dbpanelModel = new \App\Models\DbpanelModel();
$modelFields = $dbpanelModel->getPanelFields('PANEL1');
echo "Found " . count($modelFields) . " fields via model\n";
foreach ($modelFields as $field) {
    echo "Field: {$field['field_name']} | Type: {$field['field_type']} | Role: {$field['field_role']}\n";
}
