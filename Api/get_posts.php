<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'headers.php';
include 'connection.php';

if (!$con) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// Session / Auth Logic
session_start();
header('Content-Type: application/json');
date_default_timezone_set("Asia/Kolkata");

// 1. Get User ID from Request or Session
$user_id = 0;
if(isset($_REQUEST['user_id']) && intval($_REQUEST['user_id']) > 0){
    $user_id = intval($_REQUEST['user_id']);
} else if(isset($_SESSION['sadhu_user_id'])){
    $user_email = $_SESSION['sadhu_user_id'];
    $user = $con->query("SELECT id FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
    if($user) $user_id = $user['id'];
}

// 2. Handle Actions
$action = $_REQUEST['action'] ?? '';

// ACTION: LIKE
if($action === 'like'){
    if($user_id <= 0){ echo json_encode(["status" => "error", "message" => "Auth failed"]); exit; }
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

// ACTION: COMMENT
if($action === 'comment'){
    if($user_id <= 0){ echo json_encode(["status" => "error", "message" => "Auth failed"]); exit; }
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

// ACTION: FETCH COMMENTS
if($action === 'fetch_comments'){
    $pid = intval($_REQUEST['id']);
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
            'profile_photo' => htmlspecialchars($c['profile_photo']),
            'comment' => htmlspecialchars($c['comment']),
            'date' => date("d M Y, h:i A", strtotime($c['date']))
        ];
    }
    echo json_encode($comments);
    exit;
}

// ACTION: DEFAULT (FETCH POSTS)
// Use $user_id already resolved above


// 3. Filter Logic
$filter_user_id = 0;
if(isset($_REQUEST['filter_user_id']) && intval($_REQUEST['filter_user_id']) > 0){
    $filter_user_id = intval($_REQUEST['filter_user_id']);
}

$whereClause = "";
if ($filter_user_id > 0) {
    $whereClause = "WHERE p.user_id = '$filter_user_id'";
}

// Fetch posts matching website logic (tbl_posts p JOIN tbl_members m)
$query = "SELECT p.*, m.name, m.profile_photo 
          FROM tbl_posts p
          LEFT JOIN tbl_members m ON p.user_id = m.id 
          $whereClause
          ORDER BY p.id DESC";

$result = $con->query($query);
$posts = [];

while($p = $result->fetch_assoc()){
    $pid = intval($p['id']);

    // Likes count
    $likes_res = $con->query("SELECT COUNT(*) FROM tbl_likes WHERE post_id=$pid");
    $likes = $likes_res ? $likes_res->fetch_row()[0] : 0;

    // User liked?
    $user_liked = false;
    if($user_id > 0){
        $ul_res = $con->query("SELECT id FROM tbl_likes WHERE post_id=$pid AND user_id=$user_id");
        if($ul_res && $ul_res->num_rows > 0) $user_liked = true;
    }

    // Comments
    $comments = [];
    $cres = $con->query("SELECT c.comment, c.date, m.name, m.profile_photo 
                         FROM tbl_comments c 
                         JOIN tbl_members m ON c.user_id=m.id 
                         WHERE c.post_id=$pid 
                         ORDER BY c.date DESC");
    if($cres){
        while($c = $cres->fetch_assoc()){
            $comments[] = [
                'name' => $c['name'],
                'profile_photo' => $c['profile_photo'],
                'comment' => $c['comment'],
                'date' => $c['date']
            ];
        }
    }

    // Media
    $media = [];
    if(!empty($p['media'])){
        $media = array_values(array_filter(explode(',', $p['media'])));
    }
    // Fallback for legacy 'image' column
    if(empty($media) && !empty($p['image'])){
        $media[] = $p['image'];
    }

    $posts[] = [
        'id' => $pid,
        'user_id' => $p['user_id'],
        'name' => $p['name'] ?? 'Unknown User',
        'profile_photo' => $p['profile_photo'],
        'description' => $p['status'] ?? $p['description'] ?? '', // Map status to description for app compatibility
        'link' => $p['link'] ?? '',
        'likes' => $likes,
        'user_liked' => $user_liked,
        'comments' => $comments,
        'media' => $media, // Array of strings
        'date' => $p['created_at'] ?? $p['date']
    ];
}

echo json_encode(["status" => "success", "data" => $posts]);
?>
