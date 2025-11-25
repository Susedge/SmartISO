<?php
// Quick check to locate final-sign routes and usages
$root = dirname(__DIR__);
$routesFile = $root . '/app/Config/Routes.php';
$viewFiles = [
    $root . '/app/Views/forms/view_submission.php',
    $root . '/app/Views/forms/pending_signature.php'
];

echo "Checking routes and views for final-sign endpoints...\n";
if (!file_exists($routesFile)) { echo "Routes.php not found\n"; exit(1); }
$routesContent = file_get_contents($routesFile);
if (strpos($routesContent, "'forms/final-sign/(:num)'") !== false) {
    echo "Route mapping for 'forms/final-sign/(:num)' exists in Routes.php\n";
} else {
    echo "Route mapping for 'forms/final-sign/(:num)' NOT FOUND in Routes.php\n";
}

foreach ($viewFiles as $vf) {
    if (!file_exists($vf)) { echo "View file missing: {$vf}\n"; continue; }
    $content = file_get_contents($vf);
    if (strpos($content, "final-sign/") !== false) {
        echo "OK: 'final-sign/' found in view: {$vf}\n";
    } else {
        echo "Missing 'final-sign/' in view: {$vf}\n";
    }
}

// Sanity: show any occurrences of the old incorrect route
$all = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$count = 0;
foreach ($all as $file) {
    if ($file->isFile() && preg_match('/\.php$/', $file->getPathname())) {
        $txt = file_get_contents($file->getPathname());
        if (strpos($txt, 'final-sign-form') !== false) {
            echo "Found legacy 'final-sign-form' in: " . $file->getPathname() . "\n";
            $count++;
        }
    }
}
if ($count===0) echo "No legacy 'final-sign-form' occurrences found.\n";

echo "Done.\n";
