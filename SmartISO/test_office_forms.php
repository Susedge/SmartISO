<?php
// Test script to verify office-form association
require_once 'vendor/autoload.php';

// Load CodeIgniter
$app = \Config\Services::codeigniter();
$app->initialize();

// Connect to database
$db = \Config\Database::connect();

// Test office-form association
echo "=== TESTING OFFICE-FORM ASSOCIATION ===\n";

// Get all offices
$offices = $db->table('offices')->where('active', 1)->get()->getResultArray();
echo "Found " . count($offices) . " active offices:\n";
foreach ($offices as $office) {
    echo "- {$office['description']} (ID: {$office['id']})\n";
}

// Get forms with office information
$formsWithOffice = $db->table('forms f')
                     ->select('f.*, o.description as office_name')
                     ->join('offices o', 'o.id = f.office_id', 'left')
                     ->get()
                     ->getResultArray();

echo "\nFound " . count($formsWithOffice) . " forms with office assignments:\n";
foreach ($formsWithOffice as $form) {
    echo "- {$form['description']} -> Office: " . ($form['office_name'] ?? 'None') . "\n";
}

// Test filtering by office
if (!empty($offices)) {
    $firstOffice = $offices[0];
    echo "\n=== TESTING FILTER BY OFFICE: {$firstOffice['description']} ===\n";
    
    $formsByOffice = $db->table('forms f')
                       ->select('f.*, o.description as office_name')
                       ->join('offices o', 'o.id = f.office_id', 'left')
                       ->where('f.office_id', $firstOffice['id'])
                       ->get()
                       ->getResultArray();
    
    echo "Found " . count($formsByOffice) . " forms for this office:\n";
    foreach ($formsByOffice as $form) {
        echo "- {$form['description']}\n";
    }
}

echo "\n=== TEST COMPLETED ===\n";
