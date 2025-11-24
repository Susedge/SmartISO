<?php
/**
 * Diagnose approver dashboard counts for a given approver user id
 * Usage: php diagnose_approver_counts.php <approver_user_id>
 */

$argc = $_SERVER['argc'];
$argv = $_SERVER['argv'];

if ($argc < 2) {
    echo "Usage: php diagnose_approver_counts.php <approver_user_id>\n";
    exit(1);
}

$approverId = (int)$argv[1];

// Direct MySQLi connection - adjust credentials if needed
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get approver's department
$res = $mysqli->query("SELECT department_id, user_type FROM users WHERE id = " . $approverId . " LIMIT 1");
if (!$res) {
    die("Failed to query user: " . $mysqli->error);
}
$user = $res->fetch_assoc();
$userDept = $user ? $user['department_id'] : null;

echo "Diagnosing counts for approver user id: {$approverId}\n";
echo "User department_id: " . ($userDept ?? 'NULL') . "\n\n";

// Pending approval: submitted AND (user is signatory OR (no signatories AND form.department matches user's department))
$pendingSql = "SELECT COUNT(*) as cnt FROM form_submissions fs
    JOIN forms ON forms.id = fs.form_id
    WHERE fs.status = 'submitted' AND (
        EXISTS (SELECT 1 FROM form_signatories fsig WHERE fsig.form_id = forms.id AND fsig.user_id = {$approverId})
        OR (
            NOT EXISTS (SELECT 1 FROM form_signatories fsig2 WHERE fsig2.form_id = forms.id)
            AND forms.department_id = " . ($userDept ? $mysqli->real_escape_string($userDept) : "forms.department_id") . "
        )
    )";

$r = $mysqli->query($pendingSql);
if ($r) {
    $c = $r->fetch_assoc();
    echo "Pending Approval: " . ($c['cnt'] ?? 0) . "\n";
} else {
    echo "Pending Approval query failed: " . $mysqli->error . "\n";
}

// Approved by me but not completed
$sql = "SELECT COUNT(*) as cnt FROM form_submissions WHERE approver_id = {$approverId} AND status IN ('approved','pending_service') AND (completed IS NULL OR completed = 0) AND status != 'completed'";
$r = $mysqli->query($sql);
echo "Approved By Me (not completed): " . ($r ? ($r->fetch_assoc()['cnt'] ?? 0) : 'ERR: '.$mysqli->error) . "\n";

// Rejected by me
$sql = "SELECT COUNT(*) as cnt FROM form_submissions WHERE approver_id = {$approverId} AND status = 'rejected'";
$r = $mysqli->query($sql);
echo "Rejected By Me: " . ($r ? ($r->fetch_assoc()['cnt'] ?? 0) : 'ERR: '.$mysqli->error) . "\n";

// Completed (approved by me and marked completed)
$sql = "SELECT COUNT(*) as cnt FROM form_submissions WHERE approver_id = {$approverId} AND (completed = 1 OR status = 'completed')";
$r = $mysqli->query($sql);
echo "Completed (approved by me): " . ($r ? ($r->fetch_assoc()['cnt'] ?? 0) : 'ERR: '.$mysqli->error) . "\n";

echo "\nDetailed pending submissions (sample 25):\n";
$detailSql = "SELECT fs.id, fs.status, fs.created_at, fs.submitted_by, forms.id as form_id, forms.code, forms.department_id,
    EXISTS (SELECT 1 FROM form_signatories fsig WHERE fsig.form_id = forms.id AND fsig.user_id = {$approverId}) as is_signatory,
    (SELECT COUNT(*) FROM form_signatories fsig2 WHERE fsig2.form_id = forms.id) as signatory_count
    FROM form_submissions fs
    JOIN forms ON forms.id = fs.form_id
    WHERE fs.status = 'submitted' AND (
        EXISTS (SELECT 1 FROM form_signatories fsig WHERE fsig.form_id = forms.id AND fsig.user_id = {$approverId})
        OR (
            NOT EXISTS (SELECT 1 FROM form_signatories fsig2 WHERE fsig2.form_id = forms.id)
            AND forms.department_id = " . ($userDept ? $mysqli->real_escape_string($userDept) : "forms.department_id") . "
        )
    )
    ORDER BY fs.created_at DESC
    LIMIT 25";

$r = $mysqli->query($detailSql);
if ($r) {
    while ($row = $r->fetch_assoc()) {
        echo "Submission {$row['id']} (form {$row['form_id']} - {$row['code']}) signatories: {$row['signatory_count']} is_signatory: {$row['is_signatory']} created: {$row['created_at']}\n";
    }
} else {
    echo "Detail query failed: " . $mysqli->error . "\n";
}

$mysqli->close();

echo "\nDone.\n";
