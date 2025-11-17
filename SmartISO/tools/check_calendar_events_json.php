<?php
/**
 * Check what events are being generated for the calendar
 */

$host = 'localhost';
$database = 'smartiso';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

echo "========================================\n";
echo "CALENDAR EVENTS GENERATION CHECK\n";
echo "========================================\n\n";

$userId = 5; // Service Staff User

echo "Checking events for User ID: $userId\n\n";

// Get schedules exactly how the calendar controller does it
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        fs.form_id,
        fs.panel_name,
        fs.status as submission_status,
        f.code as form_code,
        f.description as form_description,
        u.full_name as requestor_name,
        staff.full_name as assigned_staff_name
    FROM schedules s
    LEFT JOIN form_submissions fs ON fs.id = s.submission_id
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users u ON u.id = fs.submitted_by
    LEFT JOIN users staff ON staff.id = s.assigned_staff_id
    WHERE s.assigned_staff_id = ?
    ORDER BY s.scheduled_date ASC, s.scheduled_time ASC
");
$stmt->execute([$userId]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== RAW SCHEDULES FROM DATABASE ===\n";
echo "Found: " . count($schedules) . " schedules\n\n";

if (empty($schedules)) {
    echo "✗ No schedules found!\n";
    echo "  Calendar will be empty.\n";
    exit(0);
}

// Format for calendar display (mimic Schedule controller logic)
echo "=== FORMATTED CALENDAR EVENTS ===\n\n";

$calendarEvents = [];
foreach ($schedules as $schedule) {
    $title = ($schedule['priority'] ?? 0) ? '★ ' : '';
    $title .= $schedule['form_description'] ?? $schedule['panel_name'] ?? $schedule['form_code'] ?? 'Service';
    
    // Use submission_status if available, otherwise fall back to schedule status
    $status = $schedule['submission_status'] ?? $schedule['status'] ?? 'pending';
    
    $event = [
        'id' => $schedule['id'],
        'title' => $title,
        'start' => $schedule['scheduled_date'] . 'T' . $schedule['scheduled_time'],
        'description' => $schedule['notes'] ?? '',
        'status' => $status,
        'priority' => (int)($schedule['priority'] ?? 0),
        'estimated_date' => $schedule['estimated_date'] ?? null,
        'eta_days' => isset($schedule['eta_days']) ? (int)$schedule['eta_days'] : null,
        'priority_level' => $schedule['priority_level'] ?? null
    ];
    
    $calendarEvents[] = $event;
    
    echo "Event #{$event['id']}:\n";
    echo "  Title: {$event['title']}\n";
    echo "  Start: {$event['start']}\n";
    echo "  Status: {$event['status']}\n";
    echo "  Priority Level: " . ($event['priority_level'] ?? 'none') . "\n";
    
    // Check if this is a completed event
    if ($status === 'completed') {
        echo "  --> ✓ COMPLETED EVENT (should show on calendar)\n";
    } else if ($status === 'pending_service' || $status === 'pending') {
        echo "  --> ⏳ PENDING EVENT (should show on calendar)\n";
    }
    echo "\n";
}

echo "========================================\n";
echo "CALENDAR JSON OUTPUT\n";
echo "========================================\n\n";

$json = json_encode($calendarEvents, JSON_PRETTY_PRINT);
echo $json . "\n\n";

echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n\n";

$completedCount = 0;
$pendingCount = 0;

foreach ($calendarEvents as $event) {
    if ($event['status'] === 'completed') {
        $completedCount++;
    } else {
        $pendingCount++;
    }
}

echo "Total events: " . count($calendarEvents) . "\n";
echo "Completed: $completedCount\n";
echo "Pending: $pendingCount\n\n";

if ($completedCount > 0) {
    echo "✓ Completed events EXIST in the data\n";
    echo "  If calendar shows only pending:\n";
    echo "  1. FullCalendar JavaScript filtering by date\n";
    echo "  2. Browser cache serving old events JSON\n";
    echo "  3. View source and check if JSON includes completed events\n\n";
    
    echo "NEXT STEPS:\n";
    echo "1. On other device: View page source (Ctrl+U)\n";
    echo "2. Search for 'var events =' in the source\n";
    echo "3. Check if completed events are in the JSON\n";
    echo "4. If YES → FullCalendar filtering issue\n";
    echo "5. If NO → Cache issue, force reload (Ctrl+Shift+R)\n";
} else {
    echo "⚠ No completed events in the data\n";
}
