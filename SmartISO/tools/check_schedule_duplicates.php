<?php
// Simple script to report duplicate schedule counts per submission
// Usage: php tools/check_schedule_duplicates.php

require __DIR__ . '/../vendor/autoload.php';

// Bootstrap framework DB config
$config = new \Config\Database();
$db = \Config\Database::connect();

echo "Checking schedule duplicates...\n";

try {
    $sql = "SELECT COUNT(*) as total_duplicates FROM (SELECT submission_id, COUNT(id) AS cnt FROM schedules GROUP BY submission_id HAVING COUNT(id) > 1) AS t";
    $res = $db->query($sql)->getRowArray();
    $dupCount = $res['total_duplicates'] ?? 0;

    echo "Duplicate submission_id groups: " . $dupCount . "\n";

    if ($dupCount > 0) {
        echo "Top 10 duplicate groups (submission_id -> count):\n";
        $rows = $db->query("SELECT submission_id, COUNT(id) AS cnt FROM schedules GROUP BY submission_id HAVING COUNT(id) > 1 ORDER BY cnt DESC LIMIT 10")->getResultArray();
        foreach ($rows as $r) {
            echo "  " . ($r['submission_id'] ?? 'NULL') . " => " . ($r['cnt'] ?? '0') . "\n";
        }
    } else {
        echo "No duplicates found.\n";
    }
} catch (\Throwable $e) {
    echo "Error checking duplicates: " . $e->getMessage() . "\n";
}

echo "Done.\n";
