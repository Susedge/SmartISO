<?php

require_once 'vendor/autoload.php';

// Initialize CodeIgniter
$app = \Config\Services::codeigniter();
$app->initialize();

// Load the model
$formSubmissionModel = new \App\Models\FormSubmissionModel();

echo "Testing Form Submissions Model\n";
echo "==============================\n\n";

// Test 1: Get all submissions (admin view)
echo "1. All submissions (admin view):\n";
$allSubmissions = $formSubmissionModel->getSubmissionsWithDetails(null);
echo "Count: " . count($allSubmissions) . "\n";
foreach ($allSubmissions as $submission) {
    echo "- ID: {$submission['id']}, Status: {$submission['status']}, User: {$submission['submitted_by']}, Form: {$submission['form_code']}\n";
}
echo "\n";

// Test 2: Get submissions for user ID 3
echo "2. Submissions for user 3:\n";
$userSubmissions = $formSubmissionModel->getSubmissionsWithDetails(3);
echo "Count: " . count($userSubmissions) . "\n";
foreach ($userSubmissions as $submission) {
    echo "- ID: {$submission['id']}, Status: {$submission['status']}, User: {$submission['submitted_by']}, Form: {$submission['form_code']}\n";
}
echo "\n";

// Test 3: Check pending approvals
echo "3. Pending approvals:\n";
$pendingApprovals = $formSubmissionModel->getPendingApprovals();
echo "Count: " . count($pendingApprovals) . "\n";
foreach ($pendingApprovals as $submission) {
    echo "- ID: {$submission['id']}, Status: {$submission['status']}, User: {$submission['submitted_by']}, Form: {$submission['form_code']}\n";
}
echo "\n";

echo "Test completed.\n";
