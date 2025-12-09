<?php
// Update all forms to use TAU-header.png

$pdo = new PDO('mysql:host=localhost;dbname=smartiso', 'root', '');
$stmt = $pdo->prepare("UPDATE forms SET header_image = ?");
$stmt->execute(['TAU-header.png']);

echo "Updated " . $stmt->rowCount() . " forms with TAU-header.png\n";

// Verify
$stmt = $pdo->query('SELECT id, code, header_image FROM forms');
echo "\nVerification:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    printf("ID: %d | Code: %s | Header: %s\n", $row['id'], $row['code'], $row['header_image']);
}
