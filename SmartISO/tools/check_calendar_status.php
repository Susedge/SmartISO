<?php
/**
 * Diagnostic: Check Calendar Modal Status Issue
 * 
 * This checks if completed submissions are showing as 'pending' in calendar
 */

$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "=== CALENDAR STATUS DIAGNOSTIC ===\n\n";

// Check submissions that should be completed
echo "1. COMPLETED SUBMISSIONS CHECK:\n";
$result = $mysqli->query("
    SELECT 
        fs.id,
        fs.status as submission_status,
        fs.completion_date,
        s.id as schedule_id,
        s.status as schedule_status,
        s.scheduled_date,
        f.code as form_code
    FROM form_submissions fs
    LEFT JOIN schedules s ON s.submission_id = fs.id
    LEFT JOIN forms f ON f.id = fs.form_id
    WHERE fs.status = 'completed'
    OR fs.completion_date IS NOT NULL
    ORDER BY fs.updated_at DESC
    LIMIT 10
");

if ($result->num_rows > 0) {
    echo "   Found " . $result->num_rows . " completed submissions:\n\n";
    while ($row = $result->fetch_assoc()) {
        $scheduleStatus = $row['schedule_status'] ?: 'NO SCHEDULE';
        $scheduleId = $row['schedule_id'] ?: 'N/A';
        
        echo "   📋 Submission #{$row['id']} ({$row['form_code']})\n";
        echo "      • Submission Status: {$row['submission_status']}\n";
        echo "      • Schedule ID: {$scheduleId}\n";
        echo "      • Schedule Status: {$scheduleStatus}\n";
        echo "      • Completed: {$row['completion_date']}\n";
        
        // Check if there's a mismatch
        if ($row['submission_status'] === 'completed' && $row['schedule_status'] !== 'completed' && !empty($row['schedule_id'])) {
            echo "      ⚠️ MISMATCH! Submission is completed but schedule shows '{$scheduleStatus}'\n";
            echo "      → This will cause calendar to show 'Pending' instead of 'Completed'\n";
        } else {
            echo "      ✓ Status matches correctly\n";
        }
        echo "\n";
    }
} else {
    echo "   No completed submissions found\n\n";
}

// Check what the calendar query would return
echo "2. CALENDAR QUERY SIMULATION (Service Staff View):\n";
$result = $mysqli->query("
    SELECT 
        s.id,
        s.submission_id,
        s.status as schedule_status,
        fs.status as submission_status,
        s.scheduled_date,
        s.scheduled_time,
        f.code as form_code,
        f.description as form_description
    FROM schedules s
    LEFT JOIN form_submissions fs ON fs.id = s.submission_id
    LEFT JOIN forms f ON f.id = fs.form_id
    WHERE s.assigned_staff_id IN (5, 13)
    ORDER BY s.scheduled_date DESC
    LIMIT 10
");

if ($result->num_rows > 0) {
    echo "   Calendar would show " . $result->num_rows . " events:\n\n";
    while ($row = $result->fetch_assoc()) {
        // Simulate the calendar controller logic (line 540 in Schedule.php)
        $displayStatus = $row['submission_status'] ?? $row['schedule_status'] ?? 'pending';
        
        echo "   📅 Calendar Event: {$row['form_description']} ({$row['form_code']})\n";
        echo "      • Schedule Status: {$row['schedule_status']}\n";
        echo "      • Submission Status: {$row['submission_status']}\n";
        echo "      • Display Status (what user sees): {$displayStatus}\n";
        
        if ($displayStatus !== $row['submission_status']) {
            echo "      ⚠️ Display status differs from actual submission status!\n";
        }
        echo "\n";
    }
} else {
    echo "   No scheduled items found\n\n";
}

// Check for status sync issues
echo "3. STATUS SYNC CHECK:\n";
$result = $mysqli->query("
    SELECT COUNT(*) as count
    FROM schedules s
    INNER JOIN form_submissions fs ON fs.id = s.submission_id
    WHERE fs.status = 'completed'
    AND s.status != 'completed'
");

$row = $result->fetch_assoc();
if ($row['count'] > 0) {
    echo "   ⚠️ ISSUE FOUND: {$row['count']} schedules need status update\n";
    echo "   Submission is completed but schedule status is not synced\n\n";
    
    // Show the problematic records
    $result = $mysqli->query("
        SELECT 
            s.id as schedule_id,
            s.submission_id,
            s.status as schedule_status,
            fs.status as submission_status,
            fs.completion_date,
            f.code as form_code
        FROM schedules s
        INNER JOIN form_submissions fs ON fs.id = s.submission_id
        LEFT JOIN forms f ON f.id = fs.form_id
        WHERE fs.status = 'completed'
        AND s.status != 'completed'
    ");
    
    echo "   Problematic records:\n";
    while ($row = $result->fetch_assoc()) {
        echo "   - Schedule #{$row['schedule_id']} for Submission #{$row['submission_id']} ({$row['form_code']})\n";
        echo "     Submission: {$row['submission_status']}, Schedule: {$row['schedule_status']}\n";
        echo "     SQL to fix: UPDATE schedules SET status='completed' WHERE id={$row['schedule_id']};\n\n";
    }
} else {
    echo "   ✓ All schedules are properly synced with submissions\n\n";
}

// Browser cache check
echo "4. BROWSER CACHE DETECTION:\n";
echo "   The calendar uses AJAX to load events from the server.\n";
echo "   If database is correct but user sees wrong status, it's browser cache.\n\n";
echo "   Solution for browser cache:\n";
echo "   1. Hard refresh: Ctrl+F5 or Ctrl+Shift+R\n";
echo "   2. Clear browser cache: Ctrl+Shift+Delete\n";
echo "   3. Open in Incognito/Private mode\n\n";

// Check recent updates
echo "5. RECENT STATUS UPDATES:\n";
$result = $mysqli->query("
    SELECT 
        fs.id,
        fs.status,
        fs.updated_at,
        f.code as form_code,
        u.full_name as updated_by
    FROM form_submissions fs
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users u ON u.id = fs.submitted_by
    WHERE fs.updated_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
    ORDER BY fs.updated_at DESC
    LIMIT 10
");

if ($result->num_rows > 0) {
    echo "   Submissions updated in last 24 hours:\n";
    while ($row = $result->fetch_assoc()) {
        echo "   - Submission #{$row['id']} ({$row['form_code']})\n";
        echo "     Status: {$row['status']}, Updated: {$row['updated_at']}\n";
    }
    echo "\n";
} else {
    echo "   No recent updates\n\n";
}

echo "=== SOLUTION STEPS ===\n\n";

echo "IF DATABASE IS CORRECT (Section 3 shows no issues):\n";
echo "  → Problem is BROWSER CACHE on the other device\n";
echo "  → Solution: Clear cache and hard refresh (Ctrl+F5)\n\n";

echo "IF DATABASE HAS SYNC ISSUES (Section 3 shows problems):\n";
echo "  → Run the UPDATE SQL commands shown above\n";
echo "  → OR: Complete a new service to trigger the fix\n";
echo "  → The fix in submitService() should prevent future issues\n\n";

echo "TO TEST:\n";
echo "1. On 'other device': Clear browser cache completely\n";
echo "2. Press Ctrl+F5 to hard refresh calendar page\n";
echo "3. If still shows pending, run this diagnostic again\n";
echo "4. Check if Section 3 shows any mismatches\n\n";

$mysqli->close();
echo "=== END DIAGNOSTIC ===\n";
