<?php

// Simple database connection test for analytics data
$host = 'localhost';
$db   = 'smartiso';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Testing Analytics Data for Complete Overview ===\n\n";
    
    // Get Overview Data
    echo "--- Overview Data ---\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM form_submissions");
    $totalSubmissions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM departments");
    $totalDepartments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "Total Submissions: $totalSubmissions\n";
    echo "Total Users: $totalUsers\n";
    echo "Total Departments: $totalDepartments\n\n";
    
    // Status distribution
    echo "--- Status Distribution ---\n";
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM form_submissions 
        GROUP BY status
    ");
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($statusCounts)) {
        echo "⚠️ WARNING: Status distribution is EMPTY!\n";
    } else {
        foreach ($statusCounts as $status) {
            echo "  {$status['status']}: {$status['count']}\n";
        }
    }
    echo "\n";
    
    // Form Usage
    echo "--- Form Usage Statistics ---\n";
    $stmt = $pdo->query("
        SELECT f.name as form_name, COUNT(fs.id) as usage_count
        FROM form_submissions fs
        LEFT JOIN forms f ON f.id = fs.form_id
        GROUP BY fs.form_id
        ORDER BY usage_count DESC
        LIMIT 10
    ");
    $formUsage = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($formUsage)) {
        echo "⚠️ WARNING: Form usage is EMPTY!\n";
    } else {
        foreach ($formUsage as $form) {
            echo "  {$form['form_name']}: {$form['usage_count']}\n";
        }
    }
    echo "\n";
    
    // Department Statistics
    echo "--- Department Statistics ---\n";
    $stmt = $pdo->query("
        SELECT d.name as department_name, COUNT(fs.id) as submission_count
        FROM form_submissions fs
        LEFT JOIN users u ON u.id = fs.submitted_by
        LEFT JOIN departments d ON d.id = u.department_id
        GROUP BY u.department_id
        ORDER BY submission_count DESC
    ");
    $deptStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($deptStats)) {
        echo "⚠️ WARNING: Department statistics are EMPTY!\n";
    } else {
        foreach ($deptStats as $dept) {
            $deptName = $dept['department_name'] ?: 'Unassigned';
            echo "  $deptName: {$dept['submission_count']}\n";
        }
    }
    echo "\n";
    
    // Timeline Data
    echo "--- Timeline Data (Last 30 days) ---\n";
    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM form_submissions
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $timelineData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($timelineData)) {
        echo "⚠️ WARNING: Timeline data is EMPTY!\n";
    } else {
        echo "  Total days with data: " . count($timelineData) . "\n";
        echo "  Recent entries:\n";
        foreach (array_slice($timelineData, 0, 5) as $day) {
            echo "    {$day['date']}: {$day['count']}\n";
        }
    }
    echo "\n";
    
    // Summary
    echo "=== SUMMARY ===\n";
    $issues = [];
    if ($totalSubmissions == 0) $issues[] = "No submissions in database";
    if (empty($statusCounts)) $issues[] = "Status distribution empty";
    if (empty($formUsage)) $issues[] = "Form usage empty";
    if (empty($deptStats)) $issues[] = "Department stats empty";
    if (empty($timelineData)) $issues[] = "Timeline data empty";
    
    if (empty($issues)) {
        echo "✓ All data checks passed!\n";
        echo "✓ Analytics export should work correctly.\n";
    } else {
        echo "⚠️ Issues found:\n";
        foreach ($issues as $issue) {
            echo "  - $issue\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
