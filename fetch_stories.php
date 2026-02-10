<?php
include("connection.php");
session_start();

$user_id = intval($_GET['user_id'] ?? 0);
$stories = [];
$q = $con->query("SELECT s.id, s.media, s.type, s.date, (SELECT COUNT(*) FROM tbl_story_views v WHERE v.story_id = s.id) AS views FROM tbl_stories s JOIN tbl_members m ON s.user_id=m.id WHERE s.user_id='$user_id' AND m.status!='Blocked' AND s.date > (NOW() - INTERVAL 1 DAY) ORDER BY date ASC");
while($s = $q->fetch_assoc()){
  $stories[] = [
    "id" => $s['id'],
    "media" => $s['media'],
    "type" => $s['type'],
    "date" => $s['date'],
    "views" => intval($s['views'])
  ];
}
echo json_encode($stories);
?>
