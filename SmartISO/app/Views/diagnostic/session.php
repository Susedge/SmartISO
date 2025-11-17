<!DOCTYPE html>
<html>
<head>
    <title>Session Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #007bff; color: white; font-weight: bold; }
        tr:hover { background-color: #f5f5f5; }
        .status { padding: 5px 10px; border-radius: 4px; display: inline-block; font-weight: bold; }
        .status.logged-in { background: #28a745; color: white; }
        .status.not-logged-in { background: #dc3545; color: white; }
        .info { background: #e7f3ff; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        .key-value { display: grid; grid-template-columns: 200px 1fr; gap: 10px; padding: 10px; border-bottom: 1px solid #eee; }
        .key-value:hover { background: #f9f9f9; }
        .key { font-weight: bold; color: #333; }
        .value { color: #666; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 CodeIgniter Session Diagnostic</h1>
        
        <?php if ($has_session && $is_logged_in): ?>
            <div class="info">
                <span class="status logged-in">✓ User Logged In</span>
                <p style="margin: 10px 0 0 0;"><strong>CodeIgniter session is active with user data</strong></p>
            </div>
            
            <div class="info">
                <h3 style="margin-top: 0;">👤 Current User Information:</h3>
                
                <div class="key-value">
                    <div class="key">User ID:</div>
                    <div class="value"><?= htmlspecialchars($user_id ?? 'NOT SET') ?></div>
                </div>
                
                <div class="key-value">
                    <div class="key">User Type:</div>
                    <div class="value"><strong><?= htmlspecialchars($user_type ?? 'NOT SET') ?></strong></div>
                </div>
                
                <div class="key-value">
                    <div class="key">Username:</div>
                    <div class="value"><?= htmlspecialchars($username ?? 'NOT SET') ?></div>
                </div>
                
                <div class="key-value">
                    <div class="key">Full Name:</div>
                    <div class="value"><?= htmlspecialchars($full_name ?? 'NOT SET') ?></div>
                </div>
                
                <div class="key-value">
                    <div class="key">Department ID:</div>
                    <div class="value"><?= htmlspecialchars($department_id ?? 'NOT SET') ?></div>
                </div>
                
                <div class="key-value">
                    <div class="key">Office ID:</div>
                    <div class="value"><?= htmlspecialchars($office_id ?? 'NOT SET') ?></div>
                </div>
                
                <div class="key-value">
                    <div class="key">Is Department Admin:</div>
                    <div class="value"><?= $is_department_admin ? 'YES' : 'NO' ?></div>
                </div>
            </div>
            
        <?php else: ?>
            <div class="warning">
                <span class="status not-logged-in">✗ Not Logged In</span>
                <p style="margin: 10px 0 0 0;"><strong>User is not logged in or session is invalid</strong></p>
            </div>
        <?php endif; ?>
        
        <div class="info" style="margin-top: 30px;">
            <h3>📊 Complete Session Data:</h3>
            <pre><?= htmlspecialchars(print_r($session_data, true)) ?></pre>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="<?= base_url() ?>" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">
                Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
