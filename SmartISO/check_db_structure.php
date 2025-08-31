<?php
// Simple database check
$host = 'localhost';
$dbname = 'smartiso';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== DATABASE STRUCTURE CHECK ===\n\n";
    
    // Check if office_id column exists in forms table
    echo "1. Forms table structure:\n";
    $stmt = $pdo->query("DESCRIBE forms");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $officeIdExists = false;
    foreach ($columns as $column) {
        echo "   {$column['Field']} - {$column['Type']}\n";
        if ($column['Field'] === 'office_id') {
            $officeIdExists = true;
        }
    }
    
    if ($officeIdExists) {
        echo "\n✓ office_id column found in forms table\n";
    } else {
        echo "\n✗ office_id column NOT found in forms table\n";
    }
    
    // Check offices table
    echo "\n2. Offices table:\n";
    $stmt = $pdo->query("SELECT id, code, description, active FROM offices");
    $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($offices)) {
        echo "   No offices found!\n";
    } else {
        foreach ($offices as $office) {
            echo "   ID: {$office['id']}, Code: {$office['code']}, Description: {$office['description']}, Active: {$office['active']}\n";
        }
    }
    
    // Check forms with office assignments
    echo "\n3. Forms with office assignments:\n";
    $stmt = $pdo->query("
        SELECT f.id, f.code, f.description, f.office_id, o.description as office_name
        FROM forms f
        LEFT JOIN offices o ON o.id = f.office_id
    ");
    $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($forms)) {
        echo "   No forms found!\n";
    } else {
        foreach ($forms as $form) {
            $officeName = $form['office_name'] ?? 'Not Assigned';
            echo "   ID: {$form['id']}, Code: {$form['code']}, Office: {$officeName}\n";
        }
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
