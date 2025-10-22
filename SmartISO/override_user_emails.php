<?php

/**
 * Temporary Email Override Script
 * 
 * This script backs up all current user emails and temporarily sets them to a test email address.
 * This is useful for testing email notifications without sending to real user emails.
 * 
 * Usage: php override_user_emails.php
 * 
 * To restore: php restore_user_emails.php
 */

// Database configuration
$host = 'localhost';
$dbname = 'smartiso';
$username = 'root';
$password = '';

// Configuration
$TEST_EMAIL = 'chesspiece901@gmail.com';

echo "=== Temporary Email Override Script ===\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction
    $pdo->beginTransaction();

    // Check if backup table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users_email_backup'");
    $backupTableExists = $stmt->rowCount() > 0;

    if ($backupTableExists) {
        echo "⚠️  WARNING: Backup table 'users_email_backup' already exists!\n";
        echo "This means emails were previously overridden.\n\n";
        echo "Do you want to:\n";
        echo "  1. Skip (keep current override)\n";
        echo "  2. Restore first, then apply new override\n";
        echo "  3. Drop existing backup and create new one (DANGEROUS - will lose previous backup)\n\n";
        echo "Enter choice (1-3): ";
        
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $choice = trim($line);
        fclose($handle);

        if ($choice == '1') {
            echo "\n✓ Skipping override. Exiting...\n";
            exit(0);
        } elseif ($choice == '2') {
            echo "\n⚠️  Please run 'php restore_user_emails.php' first, then run this script again.\n";
            exit(1);
        } elseif ($choice == '3') {
            echo "\n⚠️  Dropping existing backup table...\n";
            $pdo->exec("DROP TABLE IF EXISTS users_email_backup");
        } else {
            echo "\n❌ Invalid choice. Exiting...\n";
            exit(1);
        }
    }

    // Create backup table
    echo "Creating backup table...\n";
    $pdo->exec("
        CREATE TABLE users_email_backup (
            user_id INT PRIMARY KEY,
            original_email VARCHAR(255),
            backup_date DATETIME,
            INDEX(user_id)
        )
    ");

    // Backup current emails
    echo "Backing up current user emails...\n";
    $backedUpCount = $pdo->exec("
        INSERT INTO users_email_backup (user_id, original_email, backup_date)
        SELECT id, email, NOW()
        FROM users
        WHERE email IS NOT NULL AND email != ''
    ");

    echo "✓ Backed up {$backedUpCount} user emails\n\n";

    // Update all user emails to test email
    echo "Overriding all user emails to: {$TEST_EMAIL}...\n";
    $stmt = $pdo->prepare("
        UPDATE users
        SET email = ?
        WHERE email IS NOT NULL AND email != ''
    ");
    $stmt->execute([$TEST_EMAIL]);

    $updatedCount = $stmt->rowCount();

    // Complete transaction
    $pdo->commit();

    echo "✓ Updated {$updatedCount} user emails\n\n";
    echo "=== Email Override Complete ===\n";
    echo "All user emails have been temporarily set to: {$TEST_EMAIL}\n";
    echo "Original emails are backed up in 'users_email_backup' table\n\n";
    echo "To restore original emails, run: php restore_user_emails.php\n";

    // Display sample of affected users
    echo "\n=== Sample of Affected Users ===\n";
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.full_name, u.email, b.original_email
        FROM users u
        JOIN users_email_backup b ON u.id = b.user_id
        LIMIT 5
    ");
    $sampleUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($sampleUsers as $user) {
        echo sprintf(
            "  ID: %d | Username: %s | Name: %s\n  Original: %s → Current: %s\n\n",
            $user['id'],
            $user['username'],
            $user['full_name'],
            $user['original_email'],
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
