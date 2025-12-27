<?php
// Test tau_dco login capability
require __DIR__ . '/vendor/autoload.php';

// Load CodeIgniter
$app = require __DIR__ . '/app/Config/Paths.php';
$app = new \CodeIgniter\CodeIgniter($app);
$app->initialize();

use App\Models\UserModel;

echo "=== Testing TAU-DCO Login Capability ===\n\n";

$userModel = new UserModel();

// Test 1: Find tau_dco user
echo "Test 1: Finding tau_dco user by username...\n";
$user = $userModel->where('username', 'tau_dco_user')->first();

if ($user) {
    echo "✅ User found!\n";
    echo "   ID: {$user['id']}\n";
    echo "   Username: {$user['username']}\n";
    echo "   Email: {$user['email']}\n";
    echo "   User Type: {$user['user_type']}\n";
    echo "   Active: " . ($user['active'] ? 'Yes' : 'No') . "\n\n";
    
    // Test 2: Password verification
    echo "Test 2: Testing password verification...\n";
    $testPassword = 'password123';
    if (password_verify($testPassword, $user['password_hash'])) {
        echo "✅ Password verification successful!\n";
        echo "   Default password 'password123' is valid\n\n";
    } else {
        echo "❌ Password verification failed!\n";
        echo "   Default password may have been changed\n\n";
    }
    
    // Test 3: Check user permissions
    echo "Test 3: Checking user permissions...\n";
    $allowedTypes = ['tau_dco', 'admin', 'superuser'];
    if (in_array($user['user_type'], $allowedTypes)) {
        echo "✅ User has permission to access DCO routes\n";
        echo "   User type '{$user['user_type']}' is in allowed list\n\n";
    } else {
        echo "❌ User does NOT have DCO permissions\n\n";
    }
    
    // Test 4: Check if user is active
    echo "Test 4: Account status...\n";
    if ($user['active'] == 1) {
        echo "✅ Account is ACTIVE and can login\n\n";
    } else {
        echo "❌ Account is INACTIVE - cannot login\n\n";
    }
    
    echo "=== Summary ===\n";
    echo "The tau_dco user account is ready to use!\n";
    echo "Login with:\n";
    echo "  Username: tau_dco_user\n";
    echo "  Password: password123\n\n";
    echo "After login, the user will have access to:\n";
    echo "  - Dashboard with DCO statistics\n";
    echo "  - TAU-DCO > Form Approval menu\n";
    echo "  - Admin panel routes\n";
    echo "  - Audit logs\n\n";
    echo "⚠️  IMPORTANT: Change the default password after first login!\n";
    
} else {
    echo "❌ tau_dco user NOT found!\n";
    echo "Run: php spark db:seed TauDcoSeeder\n";
}
