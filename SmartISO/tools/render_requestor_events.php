<?php
// Create pseudo calendar events for requestor (user_id 3) using same logic as Schedule::index
$db=new mysqli('localhost','root','','smartiso');
if ($db->connect_error) die('connerr');
$userId=3;
$subsRes=$db->query("SELECT id,form_id,panel_name,created_at,status FROM form_submissions WHERE submitted_by={$userId} ORDER BY created_at ASC");
$subs = [];
while($r=$subsRes->fetch_assoc()) $subs[]=$r;

if (empty($subs)) { echo "No submissions for user {$userId}\n"; exit; }

$events = [];
foreach ($subs as $ps) {
    // only submitted/approved
    if (!in_array($ps['status'], ['submitted','approved'])) continue;
    $formRes = $db->query('SELECT code FROM forms WHERE id=' . intval($ps['form_id']));
    $form = $formRes->fetch_assoc();
    $events[] = [
        'id' => 'sub-' . $ps['id'],
        'title' => ($form['code'] ?? 'Service') . ' — ' . ($ps['panel_name'] ?? ''),
        'start' => (isset($ps['created_at']) ? substr($ps['created_at'],0,10) : date('Y-m-d')) . 'T09:00:00',
        'status' => $ps['status']
    ];
}

echo json_encode($events, JSON_PRETTY_PRINT);
$db->close();
?>
