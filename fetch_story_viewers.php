<?php
include("connection.php");
session_start();

$story_id = $_GET['story_id'] ?? 0;
$res = $con->query(
  "SELECT v.viewed_at, m.id AS member_id, m.name, m.profile_photo AS profile, m.email
   FROM tbl_story_views v
   JOIN tbl_members m ON v.viewer_id = m.id
   WHERE v.story_id = '$story_id' AND m.status != 'Blocked'
   ORDER BY v.viewed_at DESC"
);
$viewers = [];
while($v = $res->fetch_assoc()){
  $viewers[] = [
    'id' => $v['member_id'],
    'name' => $v['name'],
    'profile' => $v['profile'],
    
    'viewed_at' => $v['viewed_at']
  ];
}
echo json_encode($viewers);
?>
