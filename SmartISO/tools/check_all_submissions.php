<?php
// Check which users have submitted forms
$pdo = new PDO('mysql:host=localhost;dbname=smartiso', 'root', '');

echo "=== All Form Submissions ===\n\n";

$stmt = $pdo->query("
    SELECT 
        fs.id, fs.submitted_by, fs.status, fs.created_at,
        u.username, u.full_name, u.department_id, u.office_id,
        d.description as department_name
    FROM form_submissions fs
    LEFT JOIN users u ON u.id = fs.submitted_by
    LEFT JOIN departments d ON d.id = u.department_id
    ORDER BY fs.created_at DESC
    LIMIT 20
");

$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($submissions) . " total submissions\n\n";

foreach ($submissions as $sub) {
    echo "Submission ID: {$sub['id']}\n";
    echo "  Submitted By: {$sub['username']} (ID: {$sub['submitted_by']})\n";
    echo "  Department: {$sub['department_name']} (ID: {$sub['department_id']})\n";
    echo "  Office ID: {$sub['office_id']}\n";
    echo "  Status: {$sub['status']}\n";
    echo "  Created: {$sub['created_at']}\n\n";
}
