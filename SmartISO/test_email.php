<?php

/**
 * Gmail Email Configuration Verification
 * 
 * This script verifies that Gmail SMTP is configured correctly in CodeIgniter.
 * To test actual email sending, use the application (submit a request, etc.)
 * 
 * Usage: php test_email.php
 */

echo "=== Gmail Email Configuration Verification ===\n\n";

// Read and display Email.php configuration
$emailConfigFile = __DIR__ . '/app/Config/Email.php';

if (file_exists($emailConfigFile)) {
    $content = file_get_contents($emailConfigFile);
    
    // Extract key configuration values
    preg_match('/public string \$protocol = [\'"]([^\'"]+)[\'"]/', $content, $protocol);
    preg_match('/public string \$SMTPHost = [\'"]([^\'"]+)[\'"]/', $content, $host);
    preg_match('/public int \$SMTPPort = (\d+)/', $content, $port);
    preg_match('/public string \$SMTPUser = [\'"]([^\'"]+)[\'"]/', $content, $user);
    preg_match('/public string \$SMTPCrypto = [\'"]([^\'"]+)[\'"]/', $content, $crypto);
    preg_match('/public string \$fromEmail\s*= [\'"]([^\'"]+)[\'"]/', $content, $from);
    preg_match('/public string \$fromName\s*= [\'"]([^\'"]+)[\'"]/', $content, $fromName);
    preg_match('/public string \$mailType = [\'"]([^\'"]+)[\'"]/', $content, $mailType);
    
    echo "✅ Email Configuration File Found\n";
    echo "   Location: app/Config/Email.php\n\n";
    
    echo "📧 Gmail SMTP Settings:\n";
    echo "   Protocol: " . ($protocol[1] ?? 'not found') . "\n";
    echo "   Host: " . ($host[1] ?? 'not found') . "\n";
    echo "   Port: " . ($port[1] ?? 'not found') . "\n";
    echo "   User: " . ($user[1] ?? 'not found') . "\n";
    echo "   Crypto: " . ($crypto[1] ?? 'not found') . "\n";
    echo "   From Email: " . ($from[1] ?? 'not found') . "\n";
    echo "   From Name: " . ($fromName[1] ?? 'not found') . "\n";
    echo "   Mail Type: " . ($mailType[1] ?? 'not found') . "\n\n";
    
    // Verify configuration
    $errors = [];
    if (!isset($protocol[1]) || $protocol[1] !== 'smtp') {
        $errors[] = "Protocol should be 'smtp'";
    }
    if (!isset($host[1]) || $host[1] !== 'smtp.gmail.com') {
        $errors[] = "Host should be 'smtp.gmail.com'";
    }
    if (!isset($port[1]) || $port[1] != 587) {
        $errors[] = "Port should be 587";
    }
    if (!isset($crypto[1]) || $crypto[1] !== 'tls') {
        $errors[] = "Crypto should be 'tls'";
    }
    if (!isset($user[1]) || empty($user[1])) {
        $errors[] = "SMTP User is not set";
    }
    if (!isset($mailType[1]) || $mailType[1] !== 'html') {
        $errors[] = "Mail type should be 'html' for formatted emails";
    }
    
    if (empty($errors)) {
        echo "✅ Configuration Verified: All settings are correct!\n\n";
    } else {
        echo "⚠️  Configuration Issues Found:\n";
        foreach ($errors as $error) {
            echo "   - {$error}\n";
        }
        echo "\n";
    }
    
} else {
    echo "❌ Error: Email configuration file not found!\n";
    echo "   Expected: app/Config/Email.php\n\n";
    exit(1);
}

// Check EmailService library
$emailServiceFile = __DIR__ . '/app/Libraries/EmailService.php';
if (file_exists($emailServiceFile)) {
    echo "✅ EmailService Library Found\n";
    echo "   Location: app/Libraries/EmailService.php\n";
    echo "   Uses: CodeIgniter's \Config\Services::email()\n\n";
} else {
    echo "❌ EmailService library not found!\n\n";
}

// Check NotificationModel integration
$notificationModelFile = __DIR__ . '/app/Models/NotificationModel.php';
if (file_exists($notificationModelFile)) {
    $content = file_get_contents($notificationModelFile);
    if (strpos($content, 'EmailService') !== false && strpos($content, 'sendEmailNotification') !== false) {
        echo "✅ NotificationModel Integration Verified\n";
        echo "   Email sending is integrated into notification methods\n\n";
    } else {
        echo "⚠️  NotificationModel may not be fully integrated\n\n";
    }
}

echo "=== How to Test Email Sending ===\n\n";
echo "Since CodeIgniter requires full framework initialization, test emails\n";
echo "by using the actual application:\n\n";

echo "1. Override user emails for testing:\n";
echo "   php override_user_emails.php\n\n";

echo "2. Log into SmartISO and perform actions that trigger notifications:\n";
echo "   - Submit a service request (sends to approvers)\n";
echo "   - Approve a request (sends to requestor)\n";
echo "   - Assign service staff (sends to staff)\n";
echo "   - Complete service (sends to requestor)\n\n";

echo "3. Check chesspiece901@gmail.com for emails\n\n";

echo "4. Restore original emails:\n";
echo "   php restore_user_emails.php\n\n";

echo "=== Email Notification Types ===\n\n";
echo "The following events will trigger email notifications:\n";
echo "  ✉️  New submission → Approvers\n";
echo "  ✉️  Request approved → Requestor\n";
echo "  ✉️  Request rejected → Requestor\n";
echo "  ✉️  Service scheduled → Requestor\n";
echo "  ✉️  Staff assigned → Service Staff\n";
echo "  ✉️  Service completed → Requestor\n";
echo "  ✉️  Request cancelled → Related users\n\n";

echo "=== Verification Complete ===\n";
