<?php
$db=new mysqli('localhost','root','','smartiso');
if ($db->connect_error) { die('conn err'); }
$r=$db->query('SELECT id,submitted_by,created_at,status FROM form_submissions ORDER BY id DESC LIMIT 1');
$a=$r->fetch_assoc();
print_r($a);
$db->close();
?>
