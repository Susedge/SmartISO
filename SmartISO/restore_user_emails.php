<?php

/**
 * Restore Original User Emails Script
 * 
 * This script restores user emails from the backup table created by override_user_emails.php
 * 
 * Usage: php restore_user_emails.php
 */

// Database configuration
$host = 'localhost';
$dbname = 'smartiso';
$username = 'root';
$password = '';

echo "=== Restore Original User Emails Script ===\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if backup table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users_email_backup'");
    $backupTableExists = $stmt->rowCount() > 0;

    if (!$backupTableExists) {
        echo "❌ Error: Backup table 'users_email_backup' does not exist!\n";
        echo "Nothing to restore. Email override was never applied.\n";
        exit(1);
    }

    // Get backup info
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as backup_count,
            MIN(backup_date) as backup_date
        FROM users_email_backup
    ");
    $backupInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Found backup from: " . $backupInfo['backup_date'] . "\n";
    echo "Total backed up emails: " . $backupInfo['backup_count'] . "\n\n";

    // Show sample of what will be restored
    echo "=== Sample of Emails to Restore ===\n";
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.email as current_email, b.original_email
        FROM users u
        JOIN users_email_backup b ON u.id = b.user_id
        LIMIT 5
    ");
    $sampleUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($sampleUsers as $user) {
        echo sprintf(
            "  ID: %d | Username: %s\n  Current: %s → Will restore to: %s\n\n",
            $user['id'],
            $user['username'],
            $user['current_email'],
            $user['original_email']
        );
    }

    // Confirmation prompt
    echo "Do you want to restore original emails? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $confirm = trim(strtolower($line));
    fclose($handle);

    if ($confirm !== 'yes' && $confirm !== 'y') {
        echo "\n❌ Restoration cancelled.\n";
        exit(0);
    }

    echo "\n";

    // Start transaction
    $pdo->beginTransaction();

    // Restore emails
    echo "Restoring original user emails...\n";
    $stmt = $pdo->exec("
        UPDATE users u
        JOIN users_email_backup b ON u.id = b.user_id
        SET u.email = b.original_email
    ");

    $restoredCount = $stmt;

    // Drop backup table
    echo "Removing backup table...\n";
    $pdo->exec("DROP TABLE users_email_backup");

    // Complete transaction
    $pdo->commit();

    echo "✓ Restored {$restoredCount} user emails\n";
    echo "✓ Backup table removed\n\n";
    echo "=== Email Restoration Complete ===\n";
    echo "All user emails have been restored to their original values.\n";

    // Display sample of restored users
    echo "\n=== Sample of Restored Users ===\n";
    $stmt = $pdo->query("
        SELECT id, username, full_name, email
        FROM users
        WHERE email IS NOT NULL AND email != ''
        LIMIT 5
    ");
    $restoredUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($restoredUsers as $user) {
        echo sprintf(
            "  ID: %d | Username: %s | Name: %s | Email: %s\n",
            $user['id'],
            $user['username'],
            $user['full_name'],
            $user['email']
        );
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Rolling back changes...\n";
    
    // Attempt to rollback
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    echo "Transaction rolled back.\n";
    exit(1);
}
