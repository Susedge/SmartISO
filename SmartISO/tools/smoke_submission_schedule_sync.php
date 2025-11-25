<?php
// Run from CLI: php tools/smoke_submission_schedule_sync.php
// Simple smoke-test verifying that updating a submission propagates to schedules
require __DIR__ . '/../vendor/autoload.php';

use Config\Services;
use App\Models\FormSubmissionModel;
use App\Models\ScheduleModel;
use App\Models\UserModel;

$dt = new DateTime();
echo "Starting smoke test: submission->schedule sync\n";
$db = \Config\Database::connect();

$subModel = new FormSubmissionModel();
$schedModel = new ScheduleModel();
$userModel = new UserModel();

// Try to find a submission that has no schedules - otherwise reuse an existing submission
$submission = $db->table('form_submissions')->get(1)->getRowArray();
if (!$submission) {
    echo "No submissions exist in DB to test. Aborting.\n";
    exit(1);
}
$submissionId = $submission['id'];

// Create a test schedule (or find existing schedule)
$existingSchedule = $schedModel->where('submission_id', $submissionId)->first();
if ($existingSchedule) {
    echo "Found existing schedule (id={$existingSchedule['id']}) for submission {$submissionId}\n";
    $scheduleId = $existingSchedule['id'];
} else {
    $data = [
        'submission_id' => $submissionId,
        'scheduled_date' => date('Y-m-d'),
        'scheduled_time' => '09:00:00',
        'duration_minutes' => 60,
        'assigned_staff_id' => null,
        'priority_level' => 'medium',
        'location' => 'Test',
        'notes' => 'Smoke test schedule',
        'status' => 'pending'
    ];
    $scheduleId = $schedModel->insert($data);
    echo "Inserted schedule id={$scheduleId} for submission {$submissionId}\n";
}

// Find a service staff user to assign
$serviceStaff = $userModel->where('user_type', 'service_staff')->where('active', 1)->first();
if (!$serviceStaff) {
    echo "No active service_staff user found to assign - aborting test.\n";
    exit(1);
}
$staffId = $serviceStaff['id'];

// Perform update via model (this will hit overridden update and sync schedules)
$updateData = [ 'service_staff_id' => $staffId, 'status' => 'completed' ];
$res = $subModel->update($submissionId, $updateData);
if (!$res) {
    echo "Update failed - check DB and model.\n";
    exit(1);
}

after:;
$s = $schedModel->where('submission_id', $submissionId)->findAll();
if (empty($s)) {
    echo "No schedules found for submission after update - test FAIL\n";
    exit(1);
}

foreach ($s as $row) {
    echo "Schedule ID: {$row['id']} | assigned_staff_id = {$row['assigned_staff_id']} | status = {$row['status']}\n";
}

$ps = array_filter($s, function($r) use ($staffId) { return $r['assigned_staff_id'] == $staffId && $r['status'] === 'completed'; });
if (count($ps) > 0) {
    echo "SUCCESS: schedule(s) synced to service staff and marked completed.\n";
    exit(0);
}

echo "FAIL: schedules not synced as expected.\n";
exit(2);
