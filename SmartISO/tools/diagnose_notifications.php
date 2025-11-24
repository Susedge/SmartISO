<?php
// CLI diagnostic: diagnose notifications for a submission
// Usage: php tools/diagnose_notifications.php <submission_id>

if ($argc < 2) {
    echo "Usage: php tools/diagnose_notifications.php <submission_id>\n";
    exit(1);
}
$submissionId = (int)$argv[1];
if (!$submissionId) {
    echo "Invalid submission id\n";
    exit(1);
}

// DB connection parameters (can be overridden via env vars)
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'smartiso';
$dbPort = getenv('DB_PORT') ?: 3306;

echo "Connecting to DB {$dbUser}@{$dbHost}:{$dbPort}/{$dbName}\n";

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort);
if ($mysqli->connect_errno) {
    echo "DB connection failed: ({$mysqli->connect_errno}) {$mysqli->connect_error}\n";
    exit(1);
}

function fetchRow($mysqli, $sql) {
    $res = $mysqli->query($sql);
    if (!$res) return null;
    return $res->fetch_assoc();
}
function fetchAll($mysqli, $sql) {
    $res = $mysqli->query($sql);
    if (!$res) return [];
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
}

echo "\nDiagnostic for submission_id={$submissionId}\n\n";

$submission = fetchRow($mysqli, "SELECT * FROM form_submissions WHERE id = " . (int)$submissionId);
if (!$submission) {
    echo "Submission not found\n";
    exit(1);
}
print_r($submission);

$rows = fetchAll($mysqli, "SELECT * FROM notifications WHERE submission_id = " . (int)$submissionId . " ORDER BY created_at ASC");
echo "\nExisting notification rows ({" . count($rows) . "}):\n";
foreach ($rows as $r) {
    echo "- id={$r['id']} user_id={$r['user_id']} title={$r['title']} read={$r['read']} created_at={$r['created_at']}\n";
}

// Simulate recipients similar to NotificationModel::createServiceCompletionNotification
$recipients = [];
$requestorId = $submission['submitted_by'] ?? null;
$approverId = $submission['approver_id'] ?? null;
$formId = $submission['form_id'] ?? null;
if ($requestorId) $recipients[$requestorId] = 'requestor';
if ($approverId) $recipients[$approverId] = 'approver';

if ($formId) {
    $form = fetchRow($mysqli, "SELECT * FROM forms WHERE id = " . (int)$formId);
    $formDept = $form['department_id'] ?? null;
    if ($formDept) {
        $deptAdmins = fetchAll($mysqli, "SELECT id, email, active, department_id FROM users WHERE user_type = 'department_admin' AND department_id = " . (int)$formDept . " AND active = 1");
        foreach ($deptAdmins as $d) {
            $recipients[$d['id']] = 'dept_admin';
        }
    }
}

if (!empty($submission['service_staff_id'])) {
    $recipients[$submission['service_staff_id']] = 'service_staff';
}

echo "\nSimulated recipients (per current code):\n";
foreach ($recipients as $uid => $role) {
    $u = fetchRow($mysqli, "SELECT id, email, active, department_id FROM users WHERE id = " . (int)$uid);
    $email = $u['email'] ?? '(no email)';
    $active = isset($u['active']) ? $u['active'] : 'N/A';
    $dept = isset($u['department_id']) ? $u['department_id'] : 'N/A';
    echo "- user_id={$uid} role={$role} email={$email} active={$active} dept={$dept}\n";
}

echo "\nIf the requestor is missing from the existing notification rows above then notifications are not being inserted for them.\n";

$mysqli->close();

exit(0);
