<?php
$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== FORM DEPARTMENT vs REQUESTOR DEPARTMENT ===\n\n";

// Check the 5 problematic CRSRF submissions
$query = "SELECT fs.id as submission_id,
                 fs.status,
                 f.code as form_code,
                 f.description as form_desc,
                 f.department_id as form_department_id,
                 fd.description as form_department_name,
                 u.id as requestor_id,
                 u.full_name as requestor_name,
                 u.department_id as requestor_department_id,
                 rd.description as requestor_department_name,
                 s.id as schedule_id
          FROM form_submissions fs
          LEFT JOIN forms f ON fs.form_id = f.id
          LEFT JOIN departments fd ON f.department_id = fd.id
          LEFT JOIN users u ON fs.submitted_by = u.id
          LEFT JOIN departments rd ON u.department_id = rd.id
          LEFT JOIN schedules s ON s.submission_id = fs.id
          WHERE f.code = 'CRSRF'
          ORDER BY fs.id DESC
          LIMIT 10";

$result = $pdo->query($query);
$submissions = $result->fetchAll(PDO::FETCH_ASSOC);

echo "CRSRF Submissions (showing form dept vs requestor dept):\n\n";
foreach ($submissions as $sub) {
    echo "Submission ID: " . $sub['submission_id'] . "\n";
    echo "  Status: " . $sub['status'] . "\n";
    echo "  Form: " . $sub['form_code'] . " (" . $sub['form_desc'] . ")\n";
    echo "  Form Department: [" . $sub['form_department_id'] . "] " . $sub['form_department_name'] . "\n";
    echo "  Requestor: " . $sub['requestor_name'] . "\n";
    echo "  Requestor Department: [" . $sub['requestor_department_id'] . "] " . $sub['requestor_department_name'] . "\n";
    echo "  Has Schedule: " . ($sub['schedule_id'] ? "YES (ID: " . $sub['schedule_id'] . ")" : "NO") . "\n";
    echo "  ⚠️ MISMATCH: " . ($sub['form_department_id'] != $sub['requestor_department_id'] ? "YES" : "NO") . "\n";
    echo "\n";
}

echo "\n=== SUMMARY ===\n\n";
echo "The issue is:\n";
echo "1. CRSRF form belongs to IT Department (dept 22)\n";
echo "2. Requestors from other departments (e.g., Administration dept 12) submit CRSRF forms\n";
echo "3. Current filtering uses requestor's department only\n";
echo "4. IT Dept Admin should see ALL CRSRF submissions (their form), not just from IT requestors\n\n";

echo "SOLUTION:\n";
echo "Department admins should see submissions where:\n";
echo "  - Form's department_id matches their department OR\n";
echo "  - Requestor's department_id matches their department\n";
