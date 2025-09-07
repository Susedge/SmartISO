<?php
// Simple test to hit the forms index and capture HTML output
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/forms');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode === 200 && $html) {
    // Extract just the debug line for CRSRF
    if (preg_match('/DEBUG: dept=\[([^\]]*)\] office=\[([^\]]*)\]/', $html, $m)) {
        echo "Found debug: dept=[{$m[1]}] office=[{$m[2]}]\n";
    } else {
        echo "No debug line found\n";
    }
} else {
    echo "Request failed or returned non-200\n";
}
