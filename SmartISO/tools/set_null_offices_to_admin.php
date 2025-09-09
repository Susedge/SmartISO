<?php
$mysqli = new mysqli('127.0.0.1','root','','smartiso',3306);
if ($mysqli->connect_errno) { echo "CONNECT_ERROR: {$mysqli->connect_errno} {$mysqli->connect_error}\n"; exit(1); }

// Find Administration department by code or description
$res = $mysqli->query("SELECT id FROM departments WHERE code='ADM' OR description LIKE '%Administration%' LIMIT 1");
if (!$res || $res->num_rows == 0) { echo "Administration department not found.\n"; exit(1); }
$adm = $res->fetch_assoc();
$admId = $adm['id'];

// Update offices with NULL department_id
$u = $mysqli->query("UPDATE offices SET department_id = " . intval($admId) . " WHERE department_id IS NULL");
if (!$u) { echo "UPDATE_ERROR: {$mysqli->error}\n"; exit(1); }
echo "Updated offices: " . $mysqli->affected_rows . " rows to department_id={$admId}\n";
$mysqli->close();
