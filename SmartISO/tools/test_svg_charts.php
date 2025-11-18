<?php

echo "=== Testing SVG Chart Generation ===\n\n";

// Simple function to generate pie chart SVG
function generatePieChartSVG($labels, $values, $colors, $title, $width, $height) {
    $svg = '<?xml version="1.0" encoding="UTF-8"?>';
    $svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
    $svg .= '<rect width="100%" height="100%" fill="white"/>';
    
    $total = array_sum($values);
    if ($total == 0) {
        $svg .= '<text x="50%" y="50%" text-anchor="middle" fill="#999">No data</text>';
        $svg .= '</svg>';
        return $svg;
    }
    
    // Title
    $svg .= '<text x="' . ($width / 2) . '" y="25" text-anchor="middle" font-size="16" font-weight="bold" fill="#333">' . htmlspecialchars($title) . '</text>';
    
    $centerX = $width / 2;
    $centerY = ($height / 2) + 20;
    $radius = min($width, $height) / 3;
    
    $startAngle = 0;
    $legendY = $height - 60;
    $legendX = 20;
    $legendItemWidth = ($width - 40) / count($labels);
    
    foreach ($values as $i => $value) {
        $angle = ($value / $total) * 360;
        $endAngle = $startAngle + $angle;
        
        $color = $colors[$i % count($colors)];
        
        // Draw pie slice
        $x1 = $centerX + $radius * cos(deg2rad($startAngle));
        $y1 = $centerY + $radius * sin(deg2rad($startAngle));
        $x2 = $centerX + $radius * cos(deg2rad($endAngle));
        $y2 = $centerY + $radius * sin(deg2rad($endAngle));
        
        $largeArc = $angle > 180 ? 1 : 0;
        
        $svg .= '<path d="M' . $centerX . ',' . $centerY . ' L' . $x1 . ',' . $y1 . ' A' . $radius . ',' . $radius . ' 0 ' . $largeArc . ',1 ' . $x2 . ',' . $y2 . ' Z" fill="' . $color . '" stroke="white" stroke-width="2"/>';
        
        // Legend
        $legendItemX = $legendX + ($i * $legendItemWidth);
        $svg .= '<rect x="' . $legendItemX . '" y="' . $legendY . '" width="15" height="15" fill="' . $color . '"/>';
        $svg .= '<text x="' . ($legendItemX + 20) . '" y="' . ($legendY + 12) . '" font-size="11" fill="#333">' . htmlspecialchars($labels[$i]) . '</text>';
        
        $startAngle = $endAngle;
    }
    
    $svg .= '</svg>';
    return $svg;
}

// Test data
$labels = ['Completed', 'Pending Service', 'Approved'];
$values = [3, 10, 5];
$colors = ['#FFD166', '#FFADC7', '#06D6A0'];

echo "Generating pie chart SVG...\n";
$svg = generatePieChartSVG($labels, $values, $colors, 'Status Distribution', 500, 400);

// Save as SVG file
$svgFile = __DIR__ . '/test_svg_chart.svg';
file_put_contents($svgFile, $svg);
echo "✓ SVG saved to: $svgFile\n";

// Test embedding in PDF
echo "\nTesting PDF generation with SVG chart...\n";

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);

$dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SVG Chart Test</title>
</head>
<body>
    <h1>Analytics Report with SVG Charts</h1>
    <h2>Status Distribution</h2>
    <div style="text-align: center;">
        <img src="' . $dataUri . '" alt="Status Chart" style="max-width: 100%; height: auto;">
    </div>
    <p>Chart generated using inline SVG (no external services required)</p>
</body>
</html>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$output = $dompdf->output();
$testPdfPath = __DIR__ . '/test_svg_chart.pdf';
file_put_contents($testPdfPath, $output);

echo "✓ PDF generated: $testPdfPath\n";
echo "  File size: " . strlen($output) . " bytes\n";
echo "\n✓ SUCCESS! Open the PDF to see the SVG chart.\n";
