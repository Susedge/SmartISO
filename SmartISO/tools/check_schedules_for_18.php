<?php
$db=new mysqli('localhost','root','','smartiso');
if ($db->connect_error) { die('conn err'); }
$r=$db->query('SELECT * FROM schedules WHERE submission_id=18');
$found=false;
while($row=$r->fetch_assoc()){
    print_r($row);
    $found=true;
}
if(!$found) echo "No schedules for submission 18\n";
$db->close();
?>
