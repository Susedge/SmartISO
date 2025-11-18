<?php

echo "=== Testing QuickChart API with Different Approaches ===\n\n";

// Test 1: Simple chart
echo "Test 1: Simple Bar Chart\n";
$simpleChart = [
    'type' => 'bar',
    'data' => [
        'labels' => ['Q1', 'Q2', 'Q3', 'Q4'],
        'datasets' => [[
            'label' => 'Revenue',
            'data' => [50, 60, 70, 180]
        ]]
    ]
];

$url1 = 'https://quickchart.io/chart?c=' . urlencode(json_encode($simpleChart)) . '&width=500&height=300';
echo "URL: $url1\n";

$context = stream_context_create([
    'http' => ['timeout' => 10],
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
]);

$response = @file_get_contents($url1, false, $context);
if ($response) {
    echo "✓ Success! Image size: " . strlen($response) . " bytes\n\n";
} else {
    echo "✗ Failed\n";
    if (isset($http_response_header)) {
        echo "Response headers:\n";
        print_r($http_response_header);
    }
    echo "\n";
}

// Test 2: Using QuickChart shortcode API
echo "Test 2: QuickChart ShortURL API\n";
$chartConfig = json_encode([
    'type' => 'doughnut',
    'data' => [
        'labels' => ['Completed', 'Pending'],
        'datasets' => [[
            'data' => [3, 10],
            'backgroundColor' => ['#4BC0C0', '#FFCE56']
        ]]
    ]
]);

// Try POST request to get short URL
$postData = ['chart' => $chartConfig];
$postContext = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($postData),
        'timeout' => 10
    ],
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
]);

$shortUrl = @file_get_contents('https://quickchart.io/chart/create', false, $postContext);
if ($shortUrl) {
    $result = json_decode($shortUrl, true);
    if (isset($result['url'])) {
        echo "✓ Short URL created: {$result['url']}\n\n";
    }
} else {
    echo "✗ Failed to create short URL\n\n";
}

// Test 3: Direct image URL without encoding
echo "Test 3: Using base64 encoding\n";
$b64 = base64_encode(json_encode($simpleChart));
$url3 = "https://quickchart.io/chart?c=$b64&encoding=base64&width=500&height=300";
echo "URL length: " . strlen($url3) . "\n";

$response3 = @file_get_contents($url3, false, $context);
if ($response3) {
    echo "✓ Success! Image size: " . strlen($response3) . " bytes\n\n";
} else {
    echo "✗ Failed\n\n";
}

// Test 4: Check if URL is too long
echo "Test 4: Check URL length issue\n";
$complexChart = [
    'type' => 'doughnut',
    'data' => [
        'labels' => ['Completed', 'Pending Service', 'Approved', 'Rejected', 'Draft', 'Under Review'],
        'datasets' => [[
            'data' => [3, 10, 5, 2, 1, 4],
            'backgroundColor' => ['#FFD166', '#FFADC7', '#06D6A0', '#FFF3C4', '#EF476F', '#118AB2'],
            'borderColor' => ['#EABC41', '#FF9DB4', '#05C194', '#F5E8A3', '#DC3545', '#0F7A9F'],
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

$urlComplex = 'https://quickchart.io/chart?c=' . urlencode(json_encode($complexChart)) . '&width=500&height=400&backgroundColor=white&devicePixelRatio=2';
echo "Complex URL length: " . strlen($urlComplex) . " characters\n";

if (strlen($urlComplex) > 2000) {
    echo "⚠️ URL may be too long for some servers (max recommended: 2000 chars)\n";
}

$responseComplex = @file_get_contents($urlComplex, false, $context);
if ($responseComplex) {
    echo "✓ Success! Image size: " . strlen($responseComplex) . " bytes\n";
} else {
    echo "✗ Failed - URL might be too long or configuration invalid\n";
    
    // Try with simplified config
    echo "\nTrying simplified version...\n";
    unset($complexChart['options']['plugins']['title']['font']);
    unset($complexChart['data']['datasets'][0]['borderWidth']);
    
    $urlSimplified = 'https://quickchart.io/chart?c=' . urlencode(json_encode($complexChart)) . '&width=500&height=400';
    echo "Simplified URL length: " . strlen($urlSimplified) . " characters\n";
    
    $responseSimplified = @file_get_contents($urlSimplified, false, $context);
    if ($responseSimplified) {
        echo "✓ Simplified version works! Image size: " . strlen($responseSimplified) . " bytes\n";
    } else {
        echo "✗ Still failed\n";
    }
}
