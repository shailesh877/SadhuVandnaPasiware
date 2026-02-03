<?php
include("connection.php");
session_start();
header('Content-Type: application/json');
date_default_timezone_set("Asia/Kolkata");

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_email){ echo json_encode([]); exit; }

$user = $con->query("SELECT id, name FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
$user_id = $user['id'];
$action = $_REQUEST['action'] ?? '';

/* ============================
   ❤️ LIKE TOGGLE
============================ */
if($action === 'like'){
  $pid = intval($_POST['id']);
  $check = $con->query("SELECT id FROM tbl_likes WHERE post_id=$pid AND user_id=$user_id");

  if($check->num_rows == 0){
    $stmt = $con->prepare("INSERT INTO tbl_likes (post_id, user_id, date) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $pid, $user_id);
    $stmt->execute();
  } else {
    $con->query("DELETE FROM tbl_likes WHERE post_id=$pid AND user_id=$user_id");
  }

  echo json_encode(["ok" => true]);
  exit;
}

/* ============================
   💬 COMMENT INSERT
============================ */
if($action === 'comment'){
  $pid = intval($_POST['id']);
  $comment = trim($_POST['comment']);
  if($comment != ""){
    $stmt = $con->prepare("INSERT INTO tbl_comments (post_id, user_id, comment, date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $pid, $user_id, $comment);
    $stmt->execute();
  }
  echo json_encode(["ok" => true]);
  exit;
}

/* ============================
   📦 FETCH ALL POSTS (with likes/comments)
============================ */
if($action === 'fetch_all'){
  $posts = [];

  // ✅ Filter by specific user (if user_id passed)
  $where = "";
  if(isset($_GET['user_id']) && intval($_GET['user_id']) > 0){
    $uid = intval($_GET['user_id']);
    $where = "WHERE p.user_id = $uid";
  }

  $res = $con->query("
    SELECT p.*, m.name, m.profile_photo
    FROM tbl_posts p
    JOIN tbl_members m ON p.user_id = m.id
    $where
    ORDER BY p.created_at DESC
  ");

  while($p = $res->fetch_assoc()){
    $pid = $p['id'];

    // ✅ Likes count + check if current user liked
    $likes = $con->query("SELECT COUNT(*) FROM tbl_likes WHERE post_id=$pid")->fetch_row()[0];
    $user_liked = $con->query("SELECT id FROM tbl_likes WHERE post_id=$pid AND user_id=$user_id")->num_rows > 0;

    // ✅ Fetch comments
    $comments = [];
    $cres = $con->query("
      SELECT c.comment, c.date, m.name, m.profile_photo
      FROM tbl_comments c 
      JOIN tbl_members m ON c.user_id=m.id 
      WHERE c.post_id=$pid 
      ORDER BY c.date DESC
    ");

    while($c = $cres->fetch_assoc()){
      $comments[] = [
        'name' => htmlspecialchars($c['name']),
        'profile_photo' => htmlspecialchars($c['profile_photo']), // Placeholder for future profile photo
        'comment' => htmlspecialchars($c['comment']),
        'date' => date("d M Y, h:i A", strtotime($c['date']))
      ];
    }

    // ✅ Media split fix
    $media = [];
    if(!empty($p['media'])){
      $media = array_filter(explode(',', $p['media']));
    }

    $posts[] = [
      'id' => $pid,
      'user_id' => $p['user_id'],
      'name' => htmlspecialchars($p['name']),
      'profile_photo' => htmlspecialchars($p['profile_photo']),
      'status' => htmlspecialchars($p['status']),
      'link' => htmlspecialchars($p['link']),
      'likes' => $likes,
      'user_liked' => $user_liked,
      'comments' => $comments,
      'media' => array_values($media),
      'date' => date("d M Y, h:i A", strtotime($p['created_at']))
    ];
  }

  echo json_encode($posts);
  exit;
}
?>
