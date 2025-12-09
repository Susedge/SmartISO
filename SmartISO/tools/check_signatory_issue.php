<?php
$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');

echo "=== CHECKING IF DEPT ADMIN IS SIGNATORY ===\n\n";

$stmt = $pdo->query("
    SELECT fs.form_id, f.code, f.description,
           u.id as user_id, u.full_name, u.user_type
    FROM form_signatories fs
    LEFT JOIN forms f ON f.id = fs.form_id
    LEFT JOIN users u ON u.id = fs.user_id
    WHERE u.user_type = 'department_admin'
");

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    echo "No department admins are signatories.\n";
} else {
    foreach ($results as $r) {
        echo "{$r['full_name']} (ID: {$r['user_id']}, Type: {$r['user_type']})\n";
        echo "  Is signatory on: {$r['code']} - {$r['description']}\n\n";
        
        // Check submissions for this form
        $stmt2 = $pdo->prepare("
            SELECT COUNT(*) as count,
                   GROUP_CONCAT(DISTINCT status) as statuses
            FROM form_submissions
            WHERE form_id = ?
        ");
        $stmt2->execute([$r['form_id']]);
        $subInfo = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        echo "  This form has {$subInfo['count']} submission(s) with status: {$subInfo['statuses']}\n";
        echo "  ⚠️  These would appear on calendar if dept admin treated as approving authority!\n\n";
    }
}

// Check if the approving_authority code path would match department_admin
echo "\n=== CHECKING APPROVING_AUTHORITY CODE PATH ===\n\n";
echo "The calendar code has this section:\n";
echo "  elseif (\$userType === 'approving_authority') {\n";
echo "    // Gets submissions where user is signatory\n";
echo "  }\n\n";

echo "Department admin user_type: 'department_admin'\n";
echo "Match with 'approving_authority'?: NO\n\n";

echo "However, department admins CAN be signatories!\n";
echo "This means they might see submissions through a different path.\n";
