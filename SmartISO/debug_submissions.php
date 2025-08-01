<?php

require_once 'vendor/autoload.php';

// Initialize CodeIgniter environment
$_SERVER['CI_ENVIRONMENT'] = 'development';

// Initialize app
$app = \Config\Services::codeigniter();
$app->initialize();

echo "SmartISO Form Submissions Debug\n";
echo "===============================\n\n";

// Test 1: Load the model directly
try {
    $db = \Config\Database::connect();
    echo "1. Database connection: SUCCESS\n";
    
    // Test basic query
    $result = $db->query("SELECT COUNT(*) as count FROM form_submissions")->getRow();
    echo "   Total form_submissions: " . $result->count . "\n\n";
    
} catch (Exception $e) {
    echo "1. Database connection: FAILED - " . $e->getMessage() . "\n\n";
}

// Test 2: Test the model
try {
    $formSubmissionModel = new \App\Models\FormSubmissionModel();
    echo "2. FormSubmissionModel loaded: SUCCESS\n";
    
    // Test getSubmissionsWithDetails with null (admin view)
    $allSubmissions = $formSubmissionModel->getSubmissionsWithDetails(null);
    echo "   getSubmissionsWithDetails(null): " . count($allSubmissions) . " results\n";
    
    foreach ($allSubmissions as $submission) {
        $formCode = isset($submission['form_code']) ? $submission['form_code'] : 'N/A';
        echo "   - ID: {$submission['id']}, Status: {$submission['status']}, Form: {$formCode}\n";
    }
    echo "\n";
    
    // Test getSubmissionsWithDetails with user 3
    $userSubmissions = $formSubmissionModel->getSubmissionsWithDetails(3);
    echo "   getSubmissionsWithDetails(3): " . count($userSubmissions) . " results\n";
    
    foreach ($userSubmissions as $submission) {
        $formCode = isset($submission['form_code']) ? $submission['form_code'] : 'N/A';
        echo "   - ID: {$submission['id']}, Status: {$submission['status']}, Form: {$formCode}\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "2. FormSubmissionModel: FAILED - " . $e->getMessage() . "\n\n";
}

// Test 3: Test the exact query used in getSubmissionsWithDetails
try {
    $db = \Config\Database::connect();
    echo "3. Testing exact query from getSubmissionsWithDetails:\n";
    
    $builder = $db->table('form_submissions fs');
    $builder->select('fs.*');
    $builder->select('COALESCE(f.code, "Unknown") as form_code, COALESCE(f.description, "Unknown Form") as form_description')
        ->join('forms f', 'f.id = fs.form_id', 'left');
    $builder->select('COALESCE(u.full_name, "Unknown User") as submitted_by_name')
        ->join('users u', 'u.id = fs.submitted_by', 'left');
    $builder->select('approver.full_name as approver_name, approver.signature as approver_signature')
        ->join('users approver', 'approver.id = fs.approver_id', 'left');
    
    $builder->orderBy('fs.created_at', 'DESC');
    
    $results = $builder->get()->getResultArray();
    echo "   Direct query results: " . count($results) . "\n";
    
    foreach ($results as $result) {
        echo "   - ID: {$result['id']}, Status: {$result['status']}, Form: {$result['form_code']}, User: {$result['submitted_by_name']}\n";
    }
    
} catch (Exception $e) {
    echo "3. Direct query: FAILED - " . $e->getMessage() . "\n";
}

echo "\nDebug completed.\n";
