<?php

echo "=== Testing Alternative Chart Solutions ===\n\n";

// Test if GD is available
echo "1. Checking PHP GD Library...\n";
if (extension_loaded('gd')) {
    echo "✓ GD library is available\n";
    $gdInfo = gd_info();
    echo "  - GD Version: " . $gdInfo['GD Version'] . "\n";
    echo "  - PNG Support: " . ($gdInfo['PNG Support'] ? 'Yes' : 'No') . "\n";
    echo "  - JPEG Support: " . ($gdInfo['JPEG Support'] ? 'Yes' : 'No') . "\n";
    echo "  - FreeType Support: " . ($gdInfo['FreeType Support'] ? 'Yes' : 'No') . "\n";
} else {
    echo "✗ GD library not available\n";
}
echo "\n";

// Test alternative chart services
echo "2. Testing alternative chart services...\n\n";

$context = stream_context_create([
    'http' => ['timeout' => 10],
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
]);

// Test image-charts.com
echo "a) Testing image-charts.com:\n";
$imageChartsUrl = 'https://image-charts.com/chart?chs=500x300&cht=p&chd=t:60,40&chl=Hello|World';
$response = @file_get_contents($imageChartsUrl, false, $context);
if ($response) {
    echo "   ✓ image-charts.com works! Image size: " . strlen($response) . " bytes\n";
} else {
    echo "   ✗ image-charts.com failed\n";
}

// Test chart.googleapis.com
echo "b) Testing Google Charts API:\n";
$googleChartsUrl = 'https://chart.googleapis.com/chart?chs=500x300&cht=p&chd=t:60,40';
$response = @file_get_contents($googleChartsUrl, false, $context);
if ($response) {
    echo "   ✓ Google Charts API works! Image size: " . strlen($response) . " bytes\n";
} else {
    echo "   ✗ Google Charts API failed or deprecated\n";
}

// Test quickchart.io alternative endpoint
echo "c) Testing quickchart.io alternative:\n";
$quickChartAlt = 'https://quickchart.io/chart?cht=p&chd=t:60,40&chs=500x300';
$response = @file_get_contents($quickChartAlt, false, $context);
if ($response) {
    echo "   ✓ QuickChart alternative works! Image size: " . strlen($response) . " bytes\n";
} else {
    echo "   ✗ QuickChart alternative failed\n";
}

echo "\n3. Recommendation:\n";
echo "Since QuickChart.io is failing, we should:\n";
echo "- Option A: Use image-charts.com (free tier available)\n";
echo "- Option B: Generate charts using PHP GD + custom drawing\n";
echo "- Option C: Use Chart.js to render in browser, then capture\n";
echo "- Option D: Embed chart data as tables only (no visual charts)\n";
