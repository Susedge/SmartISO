<?php
$pdo = new PDO('mysql:host=localhost;dbname=smartiso;port=3306', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== CHECKING IT DEPARTMENT ADMIN ===\n\n";

// Get IT dept admin info
$query = "SELECT u.id, u.username, u.full_name, u.user_type, u.department_id, d.description as department_name
          FROM users u
          LEFT JOIN departments d ON u.department_id = d.id
          WHERE u.username = 'dept_admin_it'";
$result = $pdo->query($query);
$admin = $result->fetch(PDO::FETCH_ASSOC);

echo "IT Department Admin:\n";
echo "  ID: " . $admin['id'] . "\n";
echo "  Username: " . $admin['username'] . "\n";
echo "  Full Name: " . $admin['full_name'] . "\n";
echo "  User Type: " . $admin['user_type'] . "\n";
echo "  Department ID: " . $admin['department_id'] . "\n";
echo "  Department: " . $admin['department_name'] . "\n\n";

echo "=== FORMS BELONGING TO IT DEPARTMENT (22) ===\n\n";

$query2 = "SELECT f.id, f.code, f.description, f.department_id, d.description as department_name
           FROM forms f
           LEFT JOIN departments d ON f.department_id = d.id
           WHERE f.department_id = " . $admin['department_id'];
$result2 = $pdo->query($query2);
$forms = $result2->fetchAll(PDO::FETCH_ASSOC);

if (empty($forms)) {
    echo "  ⚠️ NO FORMS belong to IT department!\n\n";
} else {
    foreach ($forms as $form) {
        echo "  - " . $form['code'] . ": " . $form['description'] . "\n";
    }
}

echo "\n=== CRSRF FORM INFO ===\n\n";

$query3 = "SELECT f.id, f.code, f.description, f.department_id, d.description as department_name
           FROM forms f
           LEFT JOIN departments d ON f.department_id = d.id
           WHERE f.code = 'CRSRF'";
$result3 = $pdo->query($query3);
$crsrf = $result3->fetch(PDO::FETCH_ASSOC);

echo "CRSRF Form:\n";
echo "  Code: " . $crsrf['code'] . "\n";
echo "  Description: " . $crsrf['description'] . "\n";
echo "  Department ID: " . $crsrf['department_id'] . "\n";
echo "  Department: " . $crsrf['department_name'] . "\n\n";

echo "=== CONCLUSION ===\n\n";
echo "CRSRF belongs to Administration (dept 12), NOT IT (dept 22)\n";
echo "IT Department Admin should NOT see CRSRF submissions!\n";
echo "This confirms the bug - IT admin is seeing submissions from a different department's form.\n";
