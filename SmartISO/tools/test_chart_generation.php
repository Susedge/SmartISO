<?php

// Test chart generation
$chartConfig = [
    'type' => 'doughnut',
    'data' => [
        'labels' => ['Completed', 'Pending', 'Approved'],
        'datasets' => [[
            'data' => [3, 10, 5],
            'backgroundColor' => ['#FFD166', '#FFADC7', '#06D6A0'],
            'borderColor' => ['#EABC41', '#FF9DB4', '#05C194'],
            'borderWidth' => 2
        ]]
    ],
    'options' => [
        'plugins' => [
            'legend' => ['position' => 'bottom'],
            'title' => [
                'display' => true,
                'text' => 'Status Distribution',
                'font' => ['size' => 16]
            ]
        ]
    ]
];

$baseUrl = 'https://quickchart.io/chart';
$params = [
    'c' => json_encode($chartConfig),
    'width' => 500,
    'height' => 400,
    'backgroundColor' => 'white',
    'devicePixelRatio' => 2.0
];

$chartUrl = $baseUrl . '?' . http_build_query($params);

echo "=== Testing QuickChart API ===\n\n";
echo "Chart URL:\n$chartUrl\n\n";

// Test if URL is accessible
echo "Testing URL accessibility...\n";
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'PHP Test Script'
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$headers = @get_headers($chartUrl, 1, $context);
if ($headers && strpos($headers[0], '200') !== false) {
    echo "✓ URL is accessible (HTTP 200)\n";
    
    // Get image size
    $imageData = @file_get_contents($chartUrl, false, $context);
    if ($imageData) {
        echo "✓ Image data retrieved: " . strlen($imageData) . " bytes\n";
        
        // Verify it's a valid image
        $tmpFile = sys_get_temp_dir() . '/test_chart.png';
        file_put_contents($tmpFile, $imageData);
        $imageInfo = @getimagesize($tmpFile);
        if ($imageInfo) {
            echo "✓ Valid image: {$imageInfo[0]}x{$imageInfo[1]} ({$imageInfo['mime']})\n";
        } else {
            echo "✗ Invalid image data\n";
        }
        unlink($tmpFile);
    } else {
        echo "✗ Failed to retrieve image data\n";
    }
} else {
    echo "✗ URL is not accessible\n";
    echo "Headers: " . print_r($headers, true) . "\n";
}

echo "\n=== Testing Dompdf Remote Content ===\n";

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Chart Test</title>
</head>
<body>
    <h1>Chart Test</h1>
    <p>Testing chart image rendering:</p>
    <img src="' . $chartUrl . '" alt="Test Chart" style="max-width: 100%; height: auto;">
</body>
</html>
';

echo "Generating PDF with chart image...\n";
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$output = $dompdf->output();
$testPdfPath = __DIR__ . '/test_chart_output.pdf';
file_put_contents($testPdfPath, $output);

echo "✓ PDF generated: $testPdfPath\n";
echo "  File size: " . strlen($output) . " bytes\n";
echo "\nOpen the PDF to verify if the chart image appears.\n";
