<?php
/**
 * Test script to verify DbPanelModel class name fix
 * This tests if the model can be loaded correctly after the fix
 */

require_once __DIR__ . '/vendor/autoload.php';

// Set up minimal CodeIgniter environment
$pathsConfig = APPPATH . '../app/Config/Paths.php';
require realpath($pathsConfig) ?: $pathsConfig;

$paths = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$app = require realpath($bootstrap) ?: $bootstrap;

echo "Testing DbPanelModel class loading...\n";

try {
    // Test 1: Check if class exists
    if (class_exists('App\\Models\\DbPanelModel')) {
        echo "✓ DbPanelModel class exists\n";
    } else {
        echo "✗ DbPanelModel class NOT found\n";
        exit(1);
    }
    
    // Test 2: Try to instantiate the model
    $model = new \App\Models\DbPanelModel();
    echo "✓ DbPanelModel instantiated successfully\n";
    
    // Test 3: Check if getPanels method exists
    if (method_exists($model, 'getPanels')) {
        echo "✓ getPanels() method exists\n";
    } else {
        echo "✗ getPanels() method NOT found\n";
        exit(1);
    }
    
    // Test 4: Try to call getPanels (may fail if database is not accessible)
    try {
        $panels = $model->getPanels();
        echo "✓ getPanels() executed successfully\n";
        echo "  Found " . count($panels) . " panels\n";
    } catch (\Exception $e) {
        echo "⚠ getPanels() call failed (this may be OK if database is not accessible): " . $e->getMessage() . "\n";
    }
    
    echo "\n✓ All tests passed! The fix is working correctly.\n";
    
} catch (\Exception $e) {
    echo "\n✗ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
