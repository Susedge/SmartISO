<?php
// Quick check of forms table for header_image values

$pdo = new PDO('mysql:host=localhost;dbname=smartiso', 'root', '');
$stmt = $pdo->query('SELECT id, code, description, header_image FROM forms');
echo "Forms with header_image status:\n";
echo str_repeat('-', 80) . "\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $hasHeader = !empty($row['header_image']) ? 'YES: ' . $row['header_image'] : 'NO';
    printf("ID: %d | Code: %s | Header: %s\n", $row['id'], $row['code'], $hasHeader);
}
