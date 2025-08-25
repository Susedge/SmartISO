<?php
// One-off cleanup script to remove old temp files created by on-the-fly conversions
// Usage: php tools/cleanup_temp.php [days]
$days = isset($argv[1]) ? (int)$argv[1] : 1; // default: remove older than 1 day
$dir = __DIR__ . '/../writable/temp/';
if (!is_dir($dir)) {
    echo "Temp directory not found: $dir\n";
    exit(0);
}
$files = glob($dir . '*');
$cutoff = time() - ($days * 86400);
$deleted = 0;
foreach ($files as $f) {
    if (is_file($f) && filemtime($f) < $cutoff) {
        @unlink($f);
        $deleted++;
    }
}
echo "Deleted $deleted files older than $days day(s) from $dir\n";
