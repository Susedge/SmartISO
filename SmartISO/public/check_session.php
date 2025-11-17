<?php
/**
 * Session Diagnostic Script
 * 
 * This script shows the current session data including user_id and user_type.
 * Access via: http://localhost/check_session.php
 */

// Start session
session_start();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #007bff; color: white; font-weight: bold; }
        tr:hover { background-color: #f5f5f5; }
        .status { padding: 5px 10px; border-radius: 4px; display: inline-block; }
        .status.logged-in { background: #28a745; color: white; }
        .status.not-logged-in { background: #dc3545; color: white; }
        .info { background: #e7f3ff; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Session Diagnostic</h1>
        
        <?php if (!empty($_SESSION)): ?>
            <div class="info">
                <span class="status logged-in">✓ Session Active</span>
                <p style="margin: 10px 0 0 0;"><strong>Session has data</strong></p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Session Key</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION as $key => $value): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($key) ?></strong></td>
                        <td>
                            <?php 
                            if (is_array($value) || is_object($value)) {
                                echo '<pre>' . htmlspecialchars(print_r($value, true)) . '</pre>';
                            } else {
                                echo htmlspecialchars($value ?? 'NULL');
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="info" style="margin-top: 30px;">
                <h3>Key Session Values:</h3>
                <ul>
                    <li><strong>User ID:</strong> <?= htmlspecialchars($_SESSION['user_id'] ?? 'NOT SET') ?></li>
                    <li><strong>User Type:</strong> <?= htmlspecialchars($_SESSION['user_type'] ?? 'NOT SET') ?></li>
                    <li><strong>Username:</strong> <?= htmlspecialchars($_SESSION['username'] ?? 'NOT SET') ?></li>
                    <li><strong>Full Name:</strong> <?= htmlspecialchars($_SESSION['full_name'] ?? 'NOT SET') ?></li>
                    <li><strong>Department ID:</strong> <?= htmlspecialchars($_SESSION['department_id'] ?? 'NOT SET') ?></li>
                    <li><strong>Office ID:</strong> <?= htmlspecialchars($_SESSION['office_id'] ?? 'NOT SET') ?></li>
                    <li><strong>Is Department Admin:</strong> <?= isset($_SESSION['is_department_admin']) ? ($_SESSION['is_department_admin'] ? 'YES' : 'NO') : 'NOT SET' ?></li>
                </ul>
            </div>
            
        <?php else: ?>
            <div class="warning">
                <span class="status not-logged-in">✗ No Session</span>
                <p style="margin: 10px 0 0 0;"><strong>Session is empty - user is not logged in</strong></p>
            </div>
        <?php endif; ?>
        
        <div class="info" style="margin-top: 30px;">
            <h3>PHP Session Info:</h3>
            <ul>
                <li><strong>Session ID:</strong> <?= session_id() ?></li>
                <li><strong>Session Save Path:</strong> <?= session_save_path() ?></li>
                <li><strong>Session Name:</strong> <?= session_name() ?></li>
            </ul>
        </div>
        
        <p style="margin-top: 30px; color: #666; font-size: 14px;">
            <strong>Note:</strong> This diagnostic shows the raw PHP session data. CodeIgniter may store session data differently depending on configuration.
        </p>
    </div>
</body>
</html>
