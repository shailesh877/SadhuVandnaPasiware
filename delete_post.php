<?php
session_start();
include("connection.php");

if(!isset($_SESSION['sadhu_user_id'])){
    header("Location: login.php");
    exit;
}

if(!isset($_GET['id'])){
    die("Invalid request");
}

$post_id = intval($_GET['id']);

// ✅ Get post first to delete media
$post_q = $con->query("SELECT * FROM tbl_posts WHERE id='$post_id'");
$post = $post_q->fetch_assoc();

if(!$post){
    die("Post not found");
}

// ✅ SECURITY: Only owner can delete
$user = $con->query("SELECT * FROM tbl_members WHERE email='{$_SESSION['sadhu_user_id']}'")->fetch_assoc();
$user_id = $user['id'];

if($post['user_id'] != $user_id){
    die("Unauthorized access");
}

// ✅ Delete media file
if(!empty($post['media'])){
    $media_path = "uploads/posts/" . $post['media'];
    if(file_exists($media_path)){
        unlink($media_path);
    }
}

// ✅ Delete comments
$con->query("DELETE FROM tbl_comments WHERE post_id='$post_id'");

// ✅ Delete likes
$con->query("DELETE FROM tbl_likes WHERE post_id='$post_id'");

// ✅ Delete post
$con->query("DELETE FROM tbl_posts WHERE id='$post_id'");

// ✅ Redirect back
header("Location: profile.php?msg=deleted");
exit;
