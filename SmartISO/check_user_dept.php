<?php
// Bootstrap CodeIgniter
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(__DIR__);

// Load our paths config file
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();

// Load the framework bootstrap file
require $paths->systemDirectory . '/bootstrap.php';

// Create application
$app = Config\Services::codeigniter();
$app->initialize();

// Get database
$db = \Config\Database::connect();

// Get requestor_user
$user = $db->table('users')->where('username', 'requestor_user')->get()->getRowArray();

echo "=== REQUESTOR_USER DATA ===\n";
print_r($user);

if (!empty($user['department_id'])) {
    $dept = $db->table('departments')->where('id', $user['department_id'])->get()->getRowArray();
    echo "\n=== DEPARTMENT ===\n";
    print_r($dept);
}

// Check all departments
echo "\n=== ALL DEPARTMENTS ===\n";
$departments = $db->table('departments')->get()->getResultArray();
foreach ($departments as $d) {
    echo "ID: {$d['id']} | Code: {$d['code']} | Description: {$d['description']}\n";
}
