<?php

// Simple database connection test
$host = 'localhost';
$dbname = 'smartiso';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== SmartISO Form Submissions Debug ===\n\n";
    
    // 1. Check form_submissions table
    echo "1. All Form Submissions:\n";
    $stmt = $pdo->query("SELECT id, form_id, submitted_by, status, priority, created_at FROM form_submissions ORDER BY created_at DESC");
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($submissions)) {
        echo "   No submissions found!\n";
    } else {
        foreach ($submissions as $sub) {
            echo "   ID: {$sub['id']}, Form: {$sub['form_id']}, User: {$sub['submitted_by']}, Status: {$sub['status']}, Priority: {$sub['priority']}\n";
        }
    }
    
    echo "\n2. Forms table:\n";
    $stmt = $pdo->query("SELECT id, code, description FROM forms");
    $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($forms)) {
        echo "   No forms found!\n";
    } else {
        foreach ($forms as $form) {
            echo "   ID: {$form['id']}, Code: {$form['code']}, Description: {$form['description']}\n";
        }
    }
    
    echo "\n3. Users table:\n";
    $stmt = $pdo->query("SELECT id, email, user_type, full_name FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "   No users found!\n";
    } else {
        foreach ($users as $user) {
            echo "   ID: {$user['id']}, Email: {$user['email']}, Type: {$user['user_type']}, Name: {$user['full_name']}\n";
        }
    }
    
    echo "\n4. Testing JOIN query (same as getSubmissionsWithDetails):\n";
    $stmt = $pdo->query("
        SELECT fs.*, 
               COALESCE(f.code, 'Unknown') as form_code, 
               COALESCE(f.description, 'Unknown Form') as form_description,
               COALESCE(u.full_name, 'Unknown User') as submitted_by_name
        FROM form_submissions fs
        LEFT JOIN forms f ON f.id = fs.form_id
        LEFT JOIN users u ON u.id = fs.submitted_by
        ORDER BY fs.created_at DESC
    ");
    $joinedData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($joinedData)) {
        echo "   No joined data found!\n";
    } else {
        foreach ($joinedData as $data) {
            echo "   ID: {$data['id']}, Form: {$data['form_code']}, User: {$data['submitted_by_name']}, Status: {$data['status']}\n";
        }
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
