<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load CodeIgniter
$pathsPath = __DIR__ . '/../app/Config/Paths.php';
require realpath($pathsPath) ?: $pathsPath;

$paths = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';
$app = require realpath($bootstrap) ?: $bootstrap;

echo "=== Testing Analytics Export Data for Complete Overview ===\n\n";

// Get database connection
$db = \Config\Database::connect();

// Simulate the getReportData method for 'overview'
$reportType = 'overview';
$dateRange = 'last_30_days';
$filterDepartmentId = null;

echo "Report Type: $reportType\n";
echo "Date Range: $dateRange\n\n";

// Get Overview Data
echo "--- Overview Data ---\n";
$totalSubmissions = $db->table('form_submissions')->countAllResults();
$totalUsers = $db->table('users')->countAllResults();
$totalDepartments = $db->table('departments')->countAllResults();
$totalForms = $db->table('forms')->countAllResults();

echo "Total Submissions: $totalSubmissions\n";
echo "Total Users: $totalUsers\n";
echo "Total Departments: $totalDepartments\n";
echo "Total Forms: $totalForms\n";

// Status distribution
$statusBuilder = $db->table('form_submissions');
$statusBuilder->select('form_submissions.status, COUNT(*) as count');
$statusCounts = $statusBuilder->groupBy('form_submissions.status')
                             ->get()
                             ->getResultArray();

echo "\nStatus Distribution:\n";
print_r($statusCounts);

// Get Form Statistics
echo "\n--- Form Statistics ---\n";
$formUsageBuilder = $db->table('form_submissions');
$formUsageBuilder->select('forms.name as form_name, COUNT(form_submissions.id) as usage_count');
$formUsageBuilder->join('forms', 'forms.id = form_submissions.form_id', 'left');
$formUsageBuilder->groupBy('form_submissions.form_id');
$formUsageBuilder->orderBy('usage_count', 'DESC');
$formUsageBuilder->limit(10);
$formUsage = $formUsageBuilder->get()->getResultArray();

echo "Form Usage (Top 10):\n";
print_r($formUsage);

// Get Department Statistics
echo "\n--- Department Statistics ---\n";
$deptBuilder = $db->table('form_submissions');
$deptBuilder->select('departments.name as department_name, COUNT(form_submissions.id) as submission_count');
$deptBuilder->join('users', 'users.id = form_submissions.submitted_by', 'left');
$deptBuilder->join('departments', 'departments.id = users.department_id', 'left');
$deptBuilder->groupBy('users.department_id');
$deptBuilder->orderBy('submission_count', 'DESC');
$deptSubmissions = $deptBuilder->get()->getResultArray();

echo "Department Submissions:\n";
print_r($deptSubmissions);

// Get Timeline Data
echo "\n--- Timeline Data ---\n";
$timelineBuilder = $db->table('form_submissions');
$timelineBuilder->select('DATE(created_at) as date, COUNT(*) as count');
$timelineBuilder->where('created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')));
$timelineBuilder->groupBy('DATE(created_at)');
$timelineBuilder->orderBy('date', 'DESC');
$timelineBuilder->limit(30);
$dailySubmissions = $timelineBuilder->get()->getResultArray();

echo "Daily Submissions (Last 30 days):\n";
echo "Count: " . count($dailySubmissions) . "\n";
if (!empty($dailySubmissions)) {
    echo "First 5 entries:\n";
    print_r(array_slice($dailySubmissions, 0, 5));
}

echo "\n=== Data Check Complete ===\n";
echo "\nKey Findings:\n";
echo "- Total Submissions: $totalSubmissions\n";
echo "- Status Distribution Entries: " . count($statusCounts) . "\n";
echo "- Form Usage Entries: " . count($formUsage) . "\n";
echo "- Department Entries: " . count($deptSubmissions) . "\n";
echo "- Timeline Entries: " . count($dailySubmissions) . "\n";

// Check if any arrays are empty
$issues = [];
if (empty($statusCounts)) {
    $issues[] = "⚠️ Status distribution is EMPTY";
}
if (empty($formUsage)) {
    $issues[] = "⚠️ Form usage is EMPTY";
}
if (empty($deptSubmissions)) {
    $issues[] = "⚠️ Department statistics are EMPTY";
}
if (empty($dailySubmissions)) {
    $issues[] = "⚠️ Timeline data is EMPTY";
}

if (!empty($issues)) {
    echo "\n⚠️ ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "$issue\n";
    }
} else {
    echo "\n✓ All data arrays contain data - export should work!\n";
}
