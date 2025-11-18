<?php

echo "=== Testing image-charts.com with proper format ===\n\n";

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'Mozilla/5.0'
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

// Test 1: Simple pie chart
echo "Test 1: Pie Chart\n";
$url1 = 'https://image-charts.com/chart?' . http_build_query([
    'cht' => 'pd',
    'chs' => '500x400',
    'chd' => 't:3,10,5',
    'chl' => 'Completed|Pending|Approved',
    'chco' => 'FFD166,FFADC7,06D6A0',
    'chf' => 'bg,s,FFFFFF'
]);
echo "URL: $url1\n";
$response = @file_get_contents($url1, false, $context);
if ($response) {
    echo "✓ SUCCESS! Image size: " . strlen($response) . " bytes\n\n";
    file_put_contents(__DIR__ . '/test_pie_chart.png', $response);
    echo "  Saved to: " . __DIR__ . "/test_pie_chart.png\n\n";
} else {
    echo "✗ Failed\n\n";
}

// Test 2: Bar chart
echo "Test 2: Bar Chart\n";
$url2 = 'https://image-charts.com/chart?' . http_build_query([
    'cht' => 'bvs',
    'chs' => '600x350',
    'chd' => 't:50,60,70,45,80,90,65,55',
    'chl' => 'Form 1|Form 2|Form 3|Form 4|Form 5|Form 6|Form 7|Form 8',
    'chco' => 'FFADC7',
    'chf' => 'bg,s,FFFFFF',
    'chxt' => 'y',
    'chds' => 'a'
]);
echo "URL: $url2\n";
$response = @file_get_contents($url2, false, $context);
if ($response) {
    echo "✓ SUCCESS! Image size: " . strlen($response) . " bytes\n\n";
    file_put_contents(__DIR__ . '/test_bar_chart.png', $response);
    echo "  Saved to: " . __DIR__ . "/test_bar_chart.png\n\n";
} else {
    echo "✗ Failed\n\n";
}

// Test 3: Line chart
echo "Test 3: Line Chart\n";
$url3 = 'https://image-charts.com/chart?' . http_build_query([
    'cht' => 'lc',
    'chs' => '600x300',
    'chd' => 't:5,10,15,20,18,25,30,28,35,40',
    'chco' => 'FFD166',
    'chf' => 'bg,s,FFFFFF',
    'chxt' => 'x,y',
    'chds' => 'a'
]);
echo "URL: $url3\n";
$response = @file_get_contents($url3, false, $context);
if ($response) {
    echo "✓ SUCCESS! Image size: " . strlen($response) . " bytes\n\n";
    file_put_contents(__DIR__ . '/test_line_chart.png', $response);
    echo "  Saved to: " . __DIR__ . "/test_line_chart.png\n\n";
} else {
    echo "✗ Failed\n\n";
}

echo "Check the generated PNG files in the tools directory.\n";
