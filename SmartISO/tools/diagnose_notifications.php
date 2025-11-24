<?php
// CLI diagnostic: diagnose notifications for a submission
// Usage: php tools/diagnose_notifications.php <submission_id>

require __DIR__ . '/../vendor/autoload.php';

if ($argc < 2) {
    echo "Usage: php tools/diagnose_notifications.php <submission_id>\n";
    exit(1);
}
$submissionId = (int)$argv[1];
if (!$submissionId) {
    echo "Invalid submission id\n";
    exit(1);
}

// Bootstrap CodeIgniter DB access
$c = new \Config\Database();
$db = $c->connect();

echo "Diagnostic for submission_id={$submissionId}\n\n";

// Show submission
$submission = $db->table('form_submissions')->where('id', $submissionId)->get()->getRowArray();
if (!$submission) {
    echo "Submission not found\n";
    exit(1);
}
print_r($submission);

// Show notification rows
$rows = $db->table('notifications')->where('submission_id', $submissionId)->orderBy('created_at','ASC')->get()->getResultArray();
echo "\nExisting notification rows ({" . count($rows) . "}):\n";
foreach ($rows as $r) {
    echo "- id={$r['id']} user_id={$r['user_id']} title={$r['title']} read={$r['read']} created_at={$r['created_at']}\n";
}

// Simulate recipients for service completion (mirror NotificationModel logic)
require_once __DIR__ . '/../app/Models/NotificationModel.php';
require_once __DIR__ . '/../app/Models/FormSignatoryModel.php';
require_once __DIR__ . '/../app/Models/UserModel.php';
require_once __DIR__ . '/../app/Models/FormModel.php';

$nm = new \App\Models\NotificationModel();
$userModel = new \App\Models\UserModel();
$formModel = new \App\Models\FormModel();

$requestorId = $submission['submitted_by'] ?? null;
$approverId = $submission['approver_id'] ?? null;
$formId = $submission['form_id'] ?? null;

$recipients = [];
if ($requestorId) $recipients[$requestorId] = 'requestor';
if ($approverId) $recipients[$approverId] = 'approver';

// department admins via form's department
if ($formId) {
    $form = $formModel->find($formId);
    $formDept = $form['department_id'] ?? null;
    if ($formDept) {
        $deptAdmins = $userModel->where('user_type','department_admin')->where('department_id',$formDept)->where('active',1)->findAll();
        foreach ($deptAdmins as $d) {
            $recipients[$d['id']] = 'dept_admin';
        }
    }
}

// assigned service staff
if (!empty($submission['service_staff_id'])) {
    $recipients[$submission['service_staff_id']] = 'service_staff';
}

echo "\nSimulated recipients (per current code):\n";
foreach ($recipients as $uid => $role) {
    $u = $userModel->find($uid);
    $email = isset($u['email']) ? $u['email'] : '(no email)';
    $active = isset($u['active']) ? $u['active'] : 'N/A';
    $dept = isset($u['department_id']) ? $u['department_id'] : 'N/A';
    echo "- user_id={$uid} role={$role} email={$email} active={$active} dept={$dept}\n";
}

echo "\nIf the requestor is missing from the existing notification rows above then notifications are not being inserted for them.\n";

exit(0);
