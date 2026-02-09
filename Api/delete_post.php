<?php
include 'headers.php';
include 'connection.php';

$post_id = $_POST['post_id'] ?? '';
$user_id = $_POST['user_id'] ?? '';

if (!$post_id || !$user_id) {
    echo json_encode(["status" => "error", "message" => "Invalid parameters"]);
    exit;
}

// Verify ownership
$check = $con->query("SELECT media FROM tbl_posts WHERE id='$post_id' AND user_id='$user_id'");
if ($check->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Post not found or unauthorized"]);
    exit;
}

$post = $check->fetch_assoc();

// Delete Media
if (!empty($post['media'])) {
    $mediaArr = explode(',', $post['media']);
    foreach ($mediaArr as $file) {
        if (!empty($file) && file_exists("../uploads/posts/" . $file)) {
            unlink("../uploads/posts/" . $file);
        }
    }
}

// Delete Record
if ($con->query("DELETE FROM tbl_posts WHERE id='$post_id'")) {
    // Delete related likes/comments
    $con->query("DELETE FROM tbl_likes WHERE post_id='$post_id'");
    $con->query("DELETE FROM tbl_comments WHERE post_id='$post_id'");
    
    echo json_encode(["status" => "success", "message" => "Post deleted"]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}
?>
