<?php
// tools/download_fullcalendar.php
// Simple script to download FullCalendar JS/CSS assets into public/assets/vendor/fullcalendar

set_time_limit(0);
$baseDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'fullcalendar';
if (!is_dir($baseDir)) {
    if (!mkdir($baseDir, 0755, true)) {
        fwrite(STDERR, "Failed to create directory: $baseDir\n");
        exit(1);
    }
}

$files = [
    // pinned to the latest FullCalendar version (6.1.19 as of August 2025)
    'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js' => $baseDir . DIRECTORY_SEPARATOR . 'index.global.min.js',
    'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.css' => $baseDir . DIRECTORY_SEPARATOR . 'index.global.min.css'
];

function fetchUrl($url)
{
    // Try curl first
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($data === false || $code >= 400) {
            fwrite(STDERR, "curl failed for $url: $err (HTTP $code)\n");
            return false;
        }
        return $data;
    }

    // Fallback to file_get_contents
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Composer FullCalendar Downloader\r\n",
            'timeout' => 30,
            'ignore_errors' => true,
        ]
    ];
    $context = stream_context_create($opts);
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        fwrite(STDERR, "file_get_contents failed for $url\n");
        return false;
    }
    return $data;
}

$allOk = true;
foreach ($files as $k => $v) {
    // Support two formats:
    // 1) dest => [url1, url2]
    // 2) url => dest
    if (is_array($v)) {
        $dest = $k;
        $candidates = $v;
    } else {
        // assume mapping url => dest
        $candidates = [$k];
        $dest = $v;
    }

    $saved = false;
    foreach ($candidates as $url) {
        echo "Attempting $url...\n";
        $content = fetchUrl($url);
        if ($content === false) {
            // try next candidate
            continue;
        }
        if (file_put_contents($dest, $content) === false) {
            fwrite(STDERR, "Failed to write file: $dest\n");
            // we attempted and failed to write; try other candidates
            continue;
        }
        echo "Saved $url -> $dest\n";
        $saved = true;
        break;
    }
    if (!$saved) {
        fwrite(STDERR, "Failed to download any candidate for $dest\n");
        $allOk = false;
    }
}

if (!$allOk) {
    fwrite(STDERR, "One or more assets failed to download. You can run this script again or download assets manually.\n");
    exit(1);
}

echo "FullCalendar assets downloaded successfully.\n";
exit(0);
