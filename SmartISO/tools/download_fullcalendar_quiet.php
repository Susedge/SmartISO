<?php
// tools/download_fullcalendar_quiet.php
// Run the downloader but don't return a non-zero exit code so Composer doesn't fail
$cmd = PHP_BINARY . ' ' . escapeshellarg(__DIR__ . DIRECTORY_SEPARATOR . 'download_fullcalendar.php');
echo "Running FullCalendar downloader...\n";
passthru($cmd, $exitCode);
if ($exitCode !== 0) {
    fwrite(STDERR, "download_fullcalendar.php exited with code $exitCode; continuing composer install.\n");
}
// Always exit 0 so Composer won't fail the install if asset download fails
exit(0);
