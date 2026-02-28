<?php
include("connection.php");
session_start();

$my_id = $_POST['my_id'] ?? 0;
$target_id = $_POST['target_id'] ?? 0;
$platform = $_POST['platform'] ?? 'marriage';
$action = $_POST['action'] ?? ''; // 'block' or 'unblock'

if(!$my_id || !$target_id){
    echo "error";
    exit;
}

if($action === 'block'){
    // Insert ignore to avoid duplicate errors
    $stmt = $con->prepare("INSERT IGNORE INTO tbl_blocked_users (blocker_id, blocked_id, chat_platform) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $my_id, $target_id, $platform);
    if($stmt->execute()){
        echo "blocked";
    } else {
        echo "error";
    }
} elseif($action === 'unblock'){
    $stmt = $con->prepare("DELETE FROM tbl_blocked_users WHERE blocker_id=? AND blocked_id=? AND chat_platform=?");
    $stmt->bind_param("iis", $my_id, $target_id, $platform);
    if($stmt->execute()){
         echo "unblocked";
    } else {
        echo "error";
    }
} else {
    echo "invalid_action";
}
?>
