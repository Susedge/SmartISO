<?php
/**
 * Diagnostic: Check why completed submissions don't appear on calendar
 */

$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "=== CALENDAR COMPLETED SUBMISSIONS DIAGNOSTIC ===\n\n";

// Check completed submissions with schedules
echo "1. COMPLETED SUBMISSIONS WITH SCHEDULES:\n";
$result = $mysqli->query("
    SELECT 
        s.id as schedule_id,
        s.submission_id,
        s.status as schedule_status,
        s.scheduled_date,
        s.assigned_staff_id,
        fs.status as submission_status,
        fs.completion_date,
        f.code as form_code,
        staff.username as staff_username
    FROM schedules s
    INNER JOIN form_submissions fs ON fs.id = s.submission_id
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users staff ON staff.id = s.assigned_staff_id
    WHERE fs.status = 'completed'
    ORDER BY fs.completion_date DESC
");

if ($result->num_rows > 0) {
    echo "   Found " . $result->num_rows . " completed submissions WITH schedules:\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "   📅 Schedule #{$row['schedule_id']} → Submission #{$row['submission_id']} ({$row['form_code']})\n";
        echo "      • Schedule Status: {$row['schedule_status']}\n";
        echo "      • Submission Status: {$row['submission_status']}\n";
        echo "      • Scheduled Date: {$row['scheduled_date']}\n";
        echo "      • Assigned to: {$row['staff_username']} (ID: {$row['assigned_staff_id']})\n";
        echo "      • Completed: {$row['completion_date']}\n";
        echo "      ✓ Should appear on calendar for this service staff\n\n";
    }
} else {
    echo "   No completed submissions with schedules found\n\n";
}

// Check completed submissions WITHOUT schedules
echo "2. COMPLETED SUBMISSIONS WITHOUT SCHEDULES:\n";
$result = $mysqli->query("
    SELECT 
        fs.id as submission_id,
        fs.status as submission_status,
        fs.service_staff_id,
        fs.completion_date,
        f.code as form_code,
        staff.username as staff_username
    FROM form_submissions fs
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users staff ON staff.id = fs.service_staff_id
    WHERE fs.status = 'completed'
    AND NOT EXISTS (SELECT 1 FROM schedules s WHERE s.submission_id = fs.id)
");

if ($result->num_rows > 0) {
    echo "   ⚠️ Found " . $result->num_rows . " completed submissions WITHOUT schedules:\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "   📋 Submission #{$row['submission_id']} ({$row['form_code']})\n";
        echo "      • Status: {$row['submission_status']}\n";
        echo "      • Assigned to: {$row['staff_username']} (ID: {$row['service_staff_id']})\n";
        echo "      • Completed: {$row['completion_date']}\n";
        echo "      ✓ Should appear as 'virtual schedule' on calendar (line 656 in Schedule.php)\n\n";
    }
} else {
    echo "   ✓ All completed submissions have schedules\n\n";
}

// Simulate what getStaffSchedules() returns
echo "3. WHAT SERVICE STAFF WOULD SEE (getStaffSchedules query):\n";
$staffIds = [5, 13]; // Known service staff IDs

foreach ($staffIds as $staffId) {
    $result = $mysqli->query("
        SELECT 
            s.id,
            s.submission_id,
            s.status as schedule_status,
            fs.status as submission_status,
            s.scheduled_date,
            f.description as form_description
        FROM schedules s
        LEFT JOIN form_submissions fs ON fs.id = s.submission_id
        LEFT JOIN forms f ON f.id = fs.form_id
        WHERE s.assigned_staff_id = {$staffId}
        ORDER BY s.scheduled_date DESC
        LIMIT 10
    ");
    
    if ($result->num_rows > 0) {
        $staff = $mysqli->query("SELECT username, full_name FROM users WHERE id = {$staffId}")->fetch_assoc();
        echo "   Service Staff: {$staff['full_name']} ({$staff['username']})\n";
        echo "   Calendar would show " . $result->num_rows . " events:\n\n";
        
        $completedCount = 0;
        while ($row = $result->fetch_assoc()) {
            $displayStatus = $row['submission_status'] ?? $row['schedule_status'] ?? 'pending';
            
            echo "   - Schedule #{$row['id']}: {$row['form_description']}\n";
            echo "     Schedule Status: {$row['schedule_status']}, Submission Status: {$row['submission_status']}\n";
            echo "     Display Status: {$displayStatus}, Date: {$row['scheduled_date']}\n";
            
            if ($displayStatus === 'completed') {
                $completedCount++;
            }
        }
        
        echo "\n   Summary: {$completedCount} completed events (should be visible)\n\n";
    } else {
        echo "   Service Staff ID {$staffId}: No schedules found\n\n";
    }
}

// Simulate virtual schedules (submissions without schedules)
echo "4. VIRTUAL SCHEDULES (getServiceStaffSubmissionsWithoutSchedules):\n";
foreach ($staffIds as $staffId) {
    $result = $mysqli->query("
        SELECT 
            fs.id as submission_id,
            fs.status as submission_status,
            fs.created_at,
            f.description as form_description
        FROM form_submissions fs
        LEFT JOIN forms f ON f.id = fs.form_id
        WHERE fs.service_staff_id = {$staffId}
        AND NOT EXISTS (SELECT 1 FROM schedules s WHERE s.submission_id = fs.id)
        AND fs.status IN ('approved', 'pending_service', 'completed')
        ORDER BY fs.created_at DESC
    ");
    
    if ($result->num_rows > 0) {
        $staff = $mysqli->query("SELECT username, full_name FROM users WHERE id = {$staffId}")->fetch_assoc();
        echo "   Service Staff: {$staff['full_name']} ({$staff['username']})\n";
        echo "   Would show " . $result->num_rows . " virtual schedule(s):\n\n";
        
        $completedCount = 0;
        while ($row = $result->fetch_assoc()) {
            echo "   - Submission #{$row['submission_id']}: {$row['form_description']}\n";
            echo "     Status: {$row['submission_status']}, Created: {$row['created_at']}\n";
            
            if ($row['submission_status'] === 'completed') {
                $completedCount++;
            }
        }
        
        echo "\n   Summary: {$completedCount} completed virtual events (should be visible)\n\n";
    }
}

// Check if there's a date range issue
echo "5. DATE RANGE CHECK:\n";
$result = $mysqli->query("
    SELECT 
        MIN(s.scheduled_date) as earliest,
        MAX(s.scheduled_date) as latest
    FROM schedules s
    INNER JOIN form_submissions fs ON fs.id = s.submission_id
    WHERE fs.status = 'completed'
");
$row = $result->fetch_assoc();
echo "   Completed submissions date range:\n";
echo "   Earliest: {$row['earliest']}\n";
echo "   Latest: {$row['latest']}\n";
echo "   Current month: " . date('Y-m') . "\n\n";

if (strpos($row['earliest'], date('Y-m')) === false && strpos($row['latest'], date('Y-m')) === false) {
    echo "   ⚠️ WARNING: Completed events are NOT in current month!\n";
    echo "   Calendar might be showing current month by default.\n";
    echo "   User needs to navigate to correct month to see completed events.\n\n";
} else {
    echo "   ✓ Completed events are in current month range\n\n";
}

echo "=== POSSIBLE ISSUES ===\n\n";

echo "A. BROWSER CACHE (Most Likely):\n";
echo "   - Calendar events are loaded once and cached\n";
echo "   - After completing a service, browser still shows old data\n";
echo "   - Solution: Hard refresh (Ctrl+F5) or clear cache\n\n";

echo "B. WRONG MONTH DISPLAYED:\n";
echo "   - Completed events might be in a different month\n";
echo "   - Calendar defaults to current month\n";
echo "   - Solution: Navigate to the month where event was scheduled\n\n";

echo "C. WRONG USER LOGGED IN:\n";
echo "   - Different service staff sees different events\n";
echo "   - Solution: Verify logged in as same user\n\n";

echo "D. DATABASE NOT SYNCED:\n";
echo "   - If using separate databases on each device\n";
echo "   - Solution: Ensure both devices use same database\n\n";

echo "=== ACTION STEPS FOR OTHER DEVICE ===\n\n";
echo "1. Verify logged in user:\n";
echo "   - Check which service staff account is logged in\n";
echo "   - Should be User ID 5 or 13\n\n";

echo "2. Clear browser cache:\n";
echo "   - Press Ctrl+Shift+Delete\n";
echo "   - Clear 'Cached images and files'\n";
echo "   - Click Clear data\n\n";

echo "3. Hard refresh calendar:\n";
echo "   - Go to Schedule → Calendar\n";
echo "   - Press Ctrl+F5 (hard refresh)\n";
echo "   - Events should reload from database\n\n";

echo "4. Check correct month:\n";
echo "   - Use calendar navigation arrows\n";
echo "   - Look for completed events in their scheduled month\n";
echo "   - They might not be in current month\n\n";

echo "5. Test in Incognito:\n";
echo "   - Open Incognito window (Ctrl+Shift+N)\n";
echo "   - Login and check calendar\n";
echo "   - If works in Incognito → definitely cache issue\n\n";

$mysqli->close();
echo "=== END DIAGNOSTIC ===\n";
