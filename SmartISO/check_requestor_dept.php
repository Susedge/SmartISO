<?php
require 'vendor/autoload.php';

$db = \Config\Database::connect();

// Get requestor_user
$user = $db->table('users')->where('username', 'requestor_user')->get()->getRowArray();

echo "=== REQUESTOR_USER DATA ===\n";
echo "User ID: " . ($user['id'] ?? 'NULL') . "\n";
echo "Username: " . ($user['username'] ?? 'NULL') . "\n";
echo "Full Name: " . ($user['full_name'] ?? 'NULL') . "\n";
echo "Department ID: " . ($user['department_id'] ?? 'NULL') . "\n";
echo "Office ID: " . ($user['office_id'] ?? 'NULL') . "\n\n";

// Get department details
if (!empty($user['department_id'])) {
    $dept = $db->table('departments')->where('id', $user['department_id'])->get()->getRowArray();
    echo "=== DEPARTMENT DATA ===\n";
    echo "Department ID: " . ($dept['id'] ?? 'NULL') . "\n";
    echo "Department Code: " . ($dept['code'] ?? 'NULL') . "\n";
    echo "Department Description: " . ($dept['description'] ?? 'NULL') . "\n\n";
}

// Get office details
if (!empty($user['office_id'])) {
    $office = $db->table('offices')->where('id', $user['office_id'])->get()->getRowArray();
    echo "=== OFFICE DATA ===\n";
    echo "Office ID: " . ($office['id'] ?? 'NULL') . "\n";
    echo "Office Code: " . ($office['code'] ?? 'NULL') . "\n";
    echo "Office Description: " . ($office['description'] ?? 'NULL') . "\n";
    echo "Office Department ID: " . ($office['department_id'] ?? 'NULL') . "\n\n";
}

// List all departments
echo "=== ALL DEPARTMENTS ===\n";
$departments = $db->table('departments')->get()->getResultArray();
foreach ($departments as $d) {
    echo "ID: {$d['id']} | Code: {$d['code']} | Description: {$d['description']}\n";
}
