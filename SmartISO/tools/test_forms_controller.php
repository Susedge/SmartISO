<?php
require_once(__DIR__ . '/../vendor/autoload.php');

// Bootstrap CodeIgniter
$pathsConfig = \Config\Paths::class;
$paths = new $pathsConfig();
$bootstrap = rtrim(realpath($paths->systemDirectory), '\\/') . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once($bootstrap);

$app = \Config\Services::codeigniter();
$app->initialize();

echo "Testing Forms Controller Filtering...\n\n";

// Mock the request with filtering parameters
$_GET['department'] = '1';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Create Forms controller
$controller = new \App\Controllers\Forms();

// Get reflection to access protected methods
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('index');
$method->setAccessible(true);

// Capture output
ob_start();
try {
    $method->invoke($controller);
    $output = ob_get_contents();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    $output = '';
}
ob_end_clean();

// Check if the filtering is working
if (strpos($output, 'Computer Repair Service Request Form') !== false) {
    echo "✓ Form found in filtered output\n";
} else {
    echo "✗ Form NOT found in filtered output\n";
}

// Test with different parameter
echo "\nTesting with non-existent department...\n";
$_GET['department'] = '99';

ob_start();
try {
    $method->invoke($controller);
    $output = ob_get_contents();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    $output = '';
}
ob_end_clean();

if (strpos($output, 'Computer Repair Service Request Form') !== false) {
    echo "✗ Form should NOT be found with invalid department\n";
} else {
    echo "✓ Form correctly filtered out with invalid department\n";
}
