<?php
include("connection.php");
session_start();

date_default_timezone_set("Asia/Kolkata");

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_email) die("Unauthorized");

$user = $con->query("SELECT id FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
$viewer_id = $user['id'];  // current user id
// Support single story_id (POST/GET) or batch via POST 'story_ids' (JSON array or comma list)
$story_ids_raw = $_POST['story_ids'] ?? null;
$single_id = intval($_POST['story_id'] ?? $_GET['story_id'] ?? 0);

$ids = [];
if($story_ids_raw){
    // try JSON decode first
    $decoded = json_decode($story_ids_raw, true);
    if(is_array($decoded)){
        foreach($decoded as $it){ $ids[] = intval($it); }
    } else {
        // fallback: comma separated
        $parts = array_filter(array_map('trim', explode(',', $story_ids_raw)));
        foreach($parts as $p){ $ids[] = intval($p); }
    }
} elseif($single_id){
    $ids[] = $single_id;
}

if(empty($ids)){
    echo json_encode(['status'=>'error','msg'=>'Invalid story id(s)']);
    exit;
}

$now = date("Y-m-d H:i:s");
$insert_stmt = $con->prepare("INSERT INTO tbl_story_views (story_id, viewer_id, viewed_at) VALUES (?, ?, ?)");
foreach($ids as $story_id){
    if(!$story_id) continue;
    // check if already viewed
    $check = $con->query("SELECT id FROM tbl_story_views WHERE story_id='$story_id' AND viewer_id='$viewer_id'");
    if($check && $check->num_rows > 0) continue;
    $insert_stmt->bind_param("iis", $story_id, $viewer_id, $now);
    $insert_stmt->execute();
}

echo json_encode(['status'=>'ok']);
?>
