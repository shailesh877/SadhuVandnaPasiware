<?php
include("connection.php");
session_start();

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_email) die("Unauthorized");

$user = $con->query("SELECT id FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
$logged_id = $user['id'];
$story_id = intval($_GET['story_id']);

// ✅ Check if story belongs to logged-in user
$check = $con->query("SELECT media FROM tbl_stories WHERE id='$story_id' AND user_id='$logged_id'");
if($check->num_rows > 0){
    $story = $check->fetch_assoc();
    $file_path =  $story['media'];

    // ✅ Delete file from folder if exists
    if(file_exists($file_path)){
        unlink($file_path);
    }

    // ✅ Delete from database
    $con->query("DELETE FROM tbl_stories WHERE id='$story_id'");

    echo "ok";
} else {
    echo "error";
}
?>
