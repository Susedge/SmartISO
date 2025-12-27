<?php
// Check current validation rules
require __DIR__ . '/vendor/autoload.php';

// Load CodeIgniter paths
$pathsConfig = require __DIR__ . '/app/Config/Paths.php';
$paths = new \Config\Paths();

// Bootstrap CodeIgniter
require rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';

$app = \Config\Services::codeigniter();
$app->initialize();

echo "=== Checking Validation Rules ===\n\n";

// Check UserModel validation
$userModel = new \App\Models\UserModel();
$rules = $userModel->getValidationRules();

echo "UserModel Validation Rules:\n";
if (isset($rules['user_type'])) {
    echo "user_type: " . $rules['user_type'] . "\n";
    if (strpos($rules['user_type'], 'tau_dco') !== false) {
        echo "✅ tau_dco is in UserModel validation rules\n\n";
    } else {
        echo "❌ tau_dco is NOT in UserModel validation rules\n\n";
    }
} else {
    echo "⚠️  user_type rule not found in UserModel\n\n";
}

// Check controller file directly
echo "Checking Users Controller file:\n";
$controllerPath = __DIR__ . '/app/Controllers/Admin/Users.php';
$controllerContent = file_get_contents($controllerPath);

$createMatches = [];
preg_match("/public function create\(\).*?'user_type'[^']*'required\|in_list\[([^\]]+)\]'/s", $controllerContent, $createMatches);

if (isset($createMatches[1])) {
    echo "create() method user_type values: " . $createMatches[1] . "\n";
    if (strpos($createMatches[1], 'tau_dco') !== false) {
        echo "✅ tau_dco is in create() validation\n\n";
    } else {
        echo "❌ tau_dco is NOT in create() validation\n\n";
    }
}

$updateMatches = [];
preg_match("/public function update\(\$id\).*?'user_type'[^']*'required\|in_list\[([^\]]+)\]'/s", $controllerContent, $updateMatches);

if (isset($updateMatches[1])) {
    echo "update() method user_type values: " . $updateMatches[1] . "\n";
    if (strpos($updateMatches[1], 'tau_dco') !== false) {
        echo "✅ tau_dco is in update() validation\n\n";
    } else {
        echo "❌ tau_dco is NOT in update() validation\n\n";
    }
}

echo "\n=== Recommendation ===\n";
echo "Try these steps:\n";
echo "1. Hard refresh the page (Ctrl+F5 or Ctrl+Shift+R)\n";
echo "2. Clear browser cache\n";
echo "3. Logout and login again\n";
echo "4. Restart Apache: net stop Apache2.4 && net start Apache2.4\n";
