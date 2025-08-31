<?php
// scripts/run_controllers_tests.php
// Runs PHPUnit to produce TestDox text and JUnit XML for controller tests.

$projectRoot = dirname(__DIR__);
$logsDir = $projectRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

// Archive older logs (move files except the primary outputs into archive folder)
$archiveName = 'archive-' . date('Ymd-His');
$archiveDir = $logsDir . DIRECTORY_SEPARATOR . $archiveName;
// Preserve user's main summary file (do not overwrite), the junit xml, and the new summary/verbose files
$keepFiles = [
    'controllers_unit_tests.txt',
    'controllers_unit_tests_summary.txt',
    'controllers_unit_tests_verbose.txt',
    'controllers_test_junit.xml'
];

$existing = glob($logsDir . DIRECTORY_SEPARATOR . '*');
foreach ($existing as $f) {
    if (is_file($f)) {
        $base = basename($f);
        if (!in_array($base, $keepFiles, true)) {
            if (!is_dir($archiveDir)) {
                mkdir($archiveDir, 0755, true);
            }
            rename($f, $archiveDir . DIRECTORY_SEPARATOR . $base);
        }
    }
}

$php = PHP_BINARY;
$phpunit = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit';

$summaryFile = $logsDir . DIRECTORY_SEPARATOR . 'controllers_unit_tests_summary.txt';
$verboseFile = $logsDir . DIRECTORY_SEPARATOR . 'controllers_unit_tests_verbose.txt';
$junitFile = $logsDir . DIRECTORY_SEPARATOR . 'controllers_test_junit.xml';
// File to store PHPUnit TestDox (human-readable) output
$testdoxFile = $logsDir . DIRECTORY_SEPARATOR . 'controllers_unit_tests.txt';

$header = "PHPUnit run: " . date('Y-m-d H:i:s') . PHP_EOL . "Command: phpunit --testdox --debug --log-junit " . basename($junitFile) . PHP_EOL . str_repeat('=', 80) . PHP_EOL;
echo "Running PHPUnit (TestDox + JUnit) -> $summaryFile and $junitFile\n";

// Run PHPUnit once, capture TestDox output and write JUnit XML.
$cmd = escapeshellcmd($php) . ' ' . escapeshellarg($phpunit) . ' --testdox --colors=never --log-junit ' . escapeshellarg($junitFile) . ' 2>&1';
$output = [];
$exit = 0;
exec($cmd, $output, $exit);

// Write full TestDox output to a dedicated file for easier inspection
file_put_contents($testdoxFile, implode(PHP_EOL, $output));

// Build a concise report: list controllers/tests first, then overall status and short logs
$reportLines = [];
$reportLines[] = "PHPUnit run: " . date('Y-m-d H:i:s');
$reportLines[] = "Summary: concise per-controller results follow.";
$reportLines[] = str_repeat('=', 60);

$failures = 0;
$errors = 0;

// Parse JUnit XML to extract controller-level stats
if (file_exists($junitFile)) {
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    if ($doc->load($junitFile)) {
        $xpath = new DOMXPath($doc);
        // Find testsuite elements that correspond to test classes (have a file attribute)
        $nodes = $xpath->query('//testsuite[@file]');
        $controllers = [];
        foreach ($nodes as $n) {
            $fileAttr = $n->getAttribute('file');
            $nameAttr = $n->getAttribute('name');
            $testsAttr = (int)$n->getAttribute('tests');
            $assertionsAttr = (int)$n->getAttribute('assertions');
            $failAttr = (int)$n->getAttribute('failures');
            $errAttr = (int)$n->getAttribute('errors');
            $timeAttr = $n->getAttribute('time');

            // Only include controllers in tests/unit/controllers
            $normalized = str_replace('\\', '/', $fileAttr);
            if (stripos($normalized, '/tests/unit/controllers/') !== false) {
                // Derive controller name: use filename without Test.php or nameAttr without Test suffix
                $base = basename($fileAttr);
                $controller = preg_replace('/Test\.php$/', '', $base);
                if (empty($controller) && preg_match('/^(.*)Test$/', $nameAttr, $m)) {
                    $controller = $m[1];
                }

                $controllers[$controller] = [
                    'tests' => $testsAttr,
                    'assertions' => $assertionsAttr,
                    'failures' => $failAttr,
                    'errors' => $errAttr,
                    'time' => $timeAttr,
                ];

                $failures += $failAttr;
                $errors += $errAttr;
            }
        }

        // Ensure consistent ordering
        ksort($controllers);

        // Add controller lines
        foreach ($controllers as $ctrl => $stats) {
            $reportLines[] = sprintf("% -25s %3d tests, %3d assertions, %1d failures, %1d errors, %s sec",
                $ctrl,
                $stats['tests'],
                $stats['assertions'],
                $stats['failures'],
                $stats['errors'],
                $stats['time']
            );
        }
    }
}

$reportLines[] = str_repeat('=', 60);

// Overall status
if ($failures === 0 && $errors === 0) {
    $reportLines[] = "OVERALL: SUCCESS";
} else {
    $reportLines[] = "OVERALL: FAILED (failures={$failures}, errors={$errors})";
}

$reportLines[] = "Short logs (last 50 lines of PHPUnit TestDox output):";
$tail = array_slice($output, -50);
foreach ($tail as $line) {
    $reportLines[] = $line;
}

// Write full verbose output to a separate file so user's edited file is preserved.
file_put_contents($verboseFile, implode(PHP_EOL, $output));

// Write concise summary report to summary file (do not touch user's controllers_unit_tests.txt)
file_put_contents($summaryFile, implode(PHP_EOL, $reportLines));

if ($failures === 0 && $errors === 0) {
    echo "Done. Summary written to $summaryFile | Verbose: $verboseFile | TestDox: $testdoxFile | JUnit: $junitFile\n";
    exit(0);
}

echo "Tests failed (see $testdoxFile and $junitFile).\n";
exit(1);
