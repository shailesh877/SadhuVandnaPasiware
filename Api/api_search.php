<?php
include("connection.php");
header('Content-Type: application/json');

$query = $_REQUEST['query'] ?? '';
$user_id = intval($_REQUEST['user_id'] ?? 0);

if (!$query) {
    echo json_encode(["ok" => true, "users" => [], "posts" => []]);
    exit;
}

$safe_query = mysqli_real_escape_string($con, $query);

// 1. Search Users
$users = [];
$user_sql = "SELECT id, name, profile_photo, city 
             FROM tbl_members 
             WHERE (name LIKE '%$safe_query%' OR city LIKE '%$safe_query%') 
             AND status != 'Blocked' 
             LIMIT 20";
$user_res = $con->query($user_sql);

while($row = $user_res->fetch_assoc()){
    if($user_id > 0 && $row['id'] != $user_id){
        // Check follow status
        $check = $con->query("SELECT status FROM tbl_followers WHERE follower_id=$user_id AND following_id=".$row['id']);
        if($r = $check->fetch_assoc()){
            $row['follow_status'] = $r['status'];
        } else {
            $row['follow_status'] = null;
        }
    } else {
        $row['follow_status'] = 'self';
    }
    $users[] = $row;
}

// 2. Search Posts
$posts = [];
// Assuming tbl_posts has a 'status' or 'description' field for text. 
// Based on get_posts.php, it uses 'status' as description.
$post_sql = "SELECT p.*, m.name, m.profile_photo 
             FROM tbl_posts p 
             JOIN tbl_members m ON p.user_id = m.id 
             WHERE p.status LIKE '%$safe_query%' 
             AND m.status != 'Blocked' 
             ORDER BY p.id DESC 
             LIMIT 20";
$post_res = $con->query($post_sql);

while($p = $post_res->fetch_assoc()){
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

    // Media processing (same as get_posts.php)
    $media = [];
    if(!empty($p['media'])){
        $media = array_values(array_filter(explode(',', $p['media'])));
    }
    if(empty($media) && !empty($p['image'])){
        $media[] = $p['image'];
    }

    $posts[] = [
        'id' => $pid,
        'user_id' => $p['user_id'],
        'name' => $p['name'] ?? 'Unknown User',
        'profile_photo' => $p['profile_photo'],
        'description' => $p['status'] ?? $p['description'] ?? '',
        'likes' => $likes,
        'user_liked' => $user_liked,
        'media' => $media,
        'date' => $p['created_at'] ?? $p['date']
    ];
}

echo json_encode([
    "ok" => true,
    "users" => $users,
    "posts" => $posts
]);
?>
