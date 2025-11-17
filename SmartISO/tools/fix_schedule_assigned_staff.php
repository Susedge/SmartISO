<?php
/**
 * FIX: Sync schedules.assigned_staff_id with form_submissions.service_staff_id
 * 
 * PROBLEM: Calendar queries by assigned_staff_id but many schedules have NULL
 * SOLUTION: Update all schedules to match their submission's service_staff_id
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
echo "FIX: Sync Schedule assigned_staff_id\n";
echo "========================================\n\n";

// First, check how many schedules need fixing
echo "=== STEP 1: Check Current State ===\n";
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_schedules,
        SUM(CASE WHEN s.assigned_staff_id IS NULL THEN 1 ELSE 0 END) as null_assigned,
        SUM(CASE WHEN s.assigned_staff_id IS NOT NULL THEN 1 ELSE 0 END) as has_assigned
    FROM schedules s
    JOIN form_submissions fs ON fs.id = s.submission_id
    WHERE fs.service_staff_id IS NOT NULL
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Schedules with service_staff assigned:\n";
echo "  Total: {$stats['total_schedules']}\n";
echo "  With assigned_staff_id: {$stats['has_assigned']}\n";
echo "  With NULL assigned_staff_id: {$stats['null_assigned']}\n\n";

if ($stats['null_assigned'] == 0) {
    echo "✓ All schedules already have assigned_staff_id set!\n";
    echo "No fix needed.\n";
    exit(0);
}

// Show specific schedules that will be updated
echo "=== STEP 2: Schedules to Update ===\n";
$stmt = $pdo->query("
    SELECT 
        s.id as schedule_id,
        s.submission_id,
        s.assigned_staff_id as current_assigned,
        fs.service_staff_id as should_be_assigned,
        fs.status as submission_status,
        u.full_name as staff_name
    FROM schedules s
    JOIN form_submissions fs ON fs.id = s.submission_id
    LEFT JOIN users u ON u.id = fs.service_staff_id
    WHERE fs.service_staff_id IS NOT NULL
      AND (s.assigned_staff_id IS NULL OR s.assigned_staff_id != fs.service_staff_id)
    ORDER BY s.id
");
$toUpdate = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($toUpdate) . " schedules to update:\n\n";
foreach ($toUpdate as $row) {
    echo "  Schedule ID: {$row['schedule_id']}\n";
    echo "    Submission: {$row['submission_id']}\n";
    echo "    Current assigned_staff_id: " . ($row['current_assigned'] ?? 'NULL') . "\n";
    echo "    Should be: {$row['should_be_assigned']} ({$row['staff_name']})\n";
    echo "    Status: {$row['submission_status']}\n";
    echo "\n";
}

// Ask for confirmation
echo "=== STEP 3: Confirm Update ===\n";
echo "This will update {$stats['null_assigned']} schedules.\n";
echo "Continue? (yes/no): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$confirm = trim(strtolower($line));
fclose($handle);

if ($confirm !== 'yes') {
    echo "\nUpdate cancelled.\n";
    exit(0);
}

// Perform the update
echo "\n=== STEP 4: Updating Schedules ===\n";
$pdo->beginTransaction();

try {
    $updateStmt = $pdo->prepare("
        UPDATE schedules s
        JOIN form_submissions fs ON fs.id = s.submission_id
        SET s.assigned_staff_id = fs.service_staff_id
        WHERE fs.service_staff_id IS NOT NULL
          AND (s.assigned_staff_id IS NULL OR s.assigned_staff_id != fs.service_staff_id)
    ");
    $updateStmt->execute();
    $rowsAffected = $updateStmt->rowCount();
    
    $pdo->commit();
    
    echo "✓ Successfully updated $rowsAffected schedules\n\n";
    
    // Verify the update
    echo "=== STEP 5: Verify Update ===\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_schedules,
            SUM(CASE WHEN s.assigned_staff_id IS NULL THEN 1 ELSE 0 END) as null_assigned,
            SUM(CASE WHEN s.assigned_staff_id IS NOT NULL THEN 1 ELSE 0 END) as has_assigned
        FROM schedules s
        JOIN form_submissions fs ON fs.id = s.submission_id
        WHERE fs.service_staff_id IS NOT NULL
    ");
    $newStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "After update:\n";
    echo "  Total: {$newStats['total_schedules']}\n";
    echo "  With assigned_staff_id: {$newStats['has_assigned']}\n";
    echo "  With NULL assigned_staff_id: {$newStats['null_assigned']}\n\n";
    
    if ($newStats['null_assigned'] == 0) {
        echo "✓ SUCCESS! All schedules now have assigned_staff_id\n\n";
        
        echo "IMPACT:\n";
        echo "  ✓ Calendar will now show all events for service staff\n";
        echo "  ✓ Service staff can see their complete schedule\n";
        echo "  ✓ No more empty calendars!\n";
    } else {
        echo "⚠ Warning: {$newStats['null_assigned']} schedules still have NULL assigned_staff_id\n";
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Changes rolled back.\n";
    exit(1);
}

echo "\n========================================\n";
echo "FIX COMPLETED!\n";
echo "========================================\n\n";
echo "Next steps:\n";
echo "1. Clear browser cache on all devices\n";
echo "2. Hard refresh calendar (Ctrl+F5)\n";
echo "3. Calendar should now show all events\n";
