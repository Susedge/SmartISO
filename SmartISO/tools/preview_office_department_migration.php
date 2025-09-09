<?php
require __DIR__ . '/../vendor/autoload.php';
$db = Config\Database::connect();
// Offices with multiple department assignments
$q = "SELECT o.id as office_id, o.code, o.description, GROUP_CONCAT(do.department_id ORDER BY do.department_id) AS dept_ids, COUNT(*) AS cnt
      FROM department_office do
      JOIN offices o ON o.id = do.office_id
      GROUP BY do.office_id
      HAVING cnt > 1";
try {
    $rows = $db->query($q)->getResultArray();
    if (empty($rows)) {
        echo "No offices with multiple departments found.\n";
    } else {
        foreach ($rows as $r) {
            echo "Office {$r['office_id']} ({$r['code']}) - {$r['description']} -> departments: {$r['dept_ids']}\n";
        }
    }
} catch (Exception $e) {
    echo "Query failed: " . $e->getMessage() . "\n";
}
