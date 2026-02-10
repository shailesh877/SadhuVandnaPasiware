<?php
include("connection.php");
session_start();

$my_id = $_POST['my_id'] ?? 0;
$target_id = $_POST['target_id'] ?? 0;
$action = $_POST['action'] ?? ''; // 'block' or 'unblock'

if(!$my_id || !$target_id){
    echo "error";
    exit;
}

if($action === 'block'){
    // Insert ignore to avoid duplicate errors
    $stmt = $con->prepare("INSERT IGNORE INTO tbl_blocked_users (blocker_id, blocked_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $my_id, $target_id);
    if($stmt->execute()){
        echo "blocked";
    } else {
        echo "error";
    }
} elseif($action === 'unblock'){
    $stmt = $con->prepare("DELETE FROM tbl_blocked_users WHERE blocker_id=? AND blocked_id=?");
    $stmt->bind_param("ii", $my_id, $target_id);
    if($stmt->execute()){
         echo "unblocked";
    } else {
        echo "error";
    }
} else {
    echo "invalid_action";
}
?>