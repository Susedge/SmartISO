<?php
/**
 * Cleanup duplicate schedules script
 *
 * Usage:
 *  php cleanup_duplicate_schedules.php         # dry-run (shows duplicates, no deletes)
 *  php cleanup_duplicate_schedules.php --apply # actually delete duplicates (keeps one per submission)
 *  php cleanup_duplicate_schedules.php --help  # show usage
 *
 * Strategy:
 *  - Find submission_id values that have more than one schedule row.
 *  - For each group, pick one schedule to KEEP using the following priority:
 *      1) schedule with is_manual_schedule = 1
 *      2) newest 'updated_at' (or created_at if updated_at is null)
 *      3) highest id as final tiebreaker
 *  - Delete all other schedule rows for that submission_id.
 *
 * Safety:
 *  - By default the script runs in dry-run mode and only prints what it WOULD delete.
 *  - Use --apply to perform deletions.
 *  - The script writes a backup CSV of deleted schedule rows to writable/logs/duplicate_schedules_deleted_YYYYmmdd_HHMMSS.csv
 */

$apply = in_array('--apply', $argv);
$help = in_array('--help', $argv) || in_array('-h', $argv);

if ($help) {
    echo "Usage:\n";
    echo "  php cleanup_duplicate_schedules.php          # dry-run (no deletes)\n";
    echo "  php cleanup_duplicate_schedules.php --apply  # perform deletions\n";
    exit(0);
}

echo "=== Cleanup Duplicate Schedules Script ===\n";
if ($apply) echo "Mode: APPLY (will delete duplicate rows)\n\n"; else echo "Mode: DRY-RUN (no changes will be made). Use --apply to delete.\n\n";

$mysqli = new mysqli('localhost', 'root', '', 'smartiso');
if ($mysqli->connect_error) {
    echo "DB connection failed: " . $mysqli->connect_error . "\n";
    exit(1);
}

// Find submission IDs with more than one schedule
$sql = "SELECT submission_id, COUNT(*) as cnt FROM schedules GROUP BY submission_id HAVING cnt > 1";
$res = $mysqli->query($sql);
if (!$res) {
    echo "Query failed: " . $mysqli->error . "\n";
    exit(1);
}

$dups = $res->fetch_all(MYSQLI_ASSOC);
$totalGroups = count($dups);
if ($totalGroups === 0) {
    echo "No duplicate schedules found.\n";
    exit(0);
}

echo "Found {$totalGroups} submission(s) with duplicate schedules.\n";

$toDelete = [];
$summary = [];

foreach ($dups as $row) {
    $submissionId = (int)$row['submission_id'];

    // Fetch schedules for this submission
    $q = $mysqli->query("SELECT * FROM schedules WHERE submission_id = {$submissionId} ORDER BY is_manual_schedule DESC, COALESCE(updated_at, created_at) DESC, id DESC");
    if (!$q) {
        echo "Failed to fetch schedules for submission {$submissionId}: " . $mysqli->error . "\n";
        continue;
    }
    $schedules = $q->fetch_all(MYSQLI_ASSOC);
    if (count($schedules) <= 1) continue;

    // Keep the first according to ordering
    $keep = array_shift($schedules); // first element
    $keptId = $keep['id'];

    $deletedIds = [];
    foreach ($schedules as $s) {
        $deletedIds[] = $s['id'];
        $toDelete[] = $s; // store full row for backup
    }

    $summary[] = [
        'submission_id' => $submissionId,
        'keep' => $keptId,
        'delete_count' => count($deletedIds),
        'delete_ids' => $deletedIds
    ];
}

// Print summary
foreach ($summary as $s) {
    echo "Submission {$s['submission_id']}: keep schedule {$s['keep']}, delete {$s['delete_count']} (" . implode(',', $s['delete_ids']) . ")\n";
}

if (empty($toDelete)) {
    echo "Nothing to delete.\n";
    exit(0);
}

if (!$apply) {
    echo "\nDRY-RUN complete. No rows deleted. Re-run with --apply to delete the duplicates.\n";
    exit(0);
}

// Proceed to delete and write backup
$timestamp = date('Ymd_His');
$logDir = __DIR__ . '/../writable/logs';
if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
$csvFile = $logDir . '/duplicate_schedules_deleted_' . $timestamp . '.csv';
$fp = fopen($csvFile, 'w');
if ($fp) {
    // Write header
    fputcsv($fp, array_keys($toDelete[0]));
}

$deletedTotal = 0;
$mysqli->begin_transaction();
try {
    foreach ($toDelete as $row) {
        $id = (int)$row['id'];
        // Insert backup row into CSV
        if ($fp) fputcsv($fp, $row);

        $delSql = "DELETE FROM schedules WHERE id = {$id}";
        if (!$mysqli->query($delSql)) {
            throw new Exception('Failed to delete schedule id ' . $id . ': ' . $mysqli->error);
        }
        $deletedTotal++;
    }
    $mysqli->commit();
    if ($fp) fclose($fp);
    echo "\nDeleted {$deletedTotal} duplicate schedule row(s). Backup saved to: {$csvFile}\n";
} catch (Exception $e) {
    $mysqli->rollback();
    if ($fp) fclose($fp);
    echo "Error during deletion: " . $e->getMessage() . "\n";
    echo "No changes were made (transaction rolled back).\n";
    exit(1);
}

echo "Done.\n";
$mysqli->close();
