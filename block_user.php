<?php
ob_start();
include("connection.php");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$my_id = intval($_POST['my_id'] ?? 0);
$target_id = intval($_POST['target_id'] ?? 0);
$platform = $_POST['platform'] ?? 'marriage';
$action = $_POST['action'] ?? ''; // 'block' or 'unblock'

if(!$my_id || !$target_id){
    ob_end_clean();
    echo "error";
    exit;
}

try {
    if($action === 'block'){
        $stmt = $con->prepare("INSERT IGNORE INTO tbl_blocked_users (blocker_id, blocked_id, chat_platform) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $my_id, $target_id, $platform);
        if($stmt->execute()){
            ob_end_clean();
            echo "blocked";
        } else {
            ob_end_clean();
            echo "error";
        }
    } elseif($action === 'unblock'){
        $stmt = $con->prepare("DELETE FROM tbl_blocked_users WHERE blocker_id=? AND blocked_id=? AND chat_platform=?");
        $stmt->bind_param("iis", $my_id, $target_id, $platform);
        if($stmt->execute()){
            ob_end_clean();
            echo "unblocked";
        } else {
            ob_end_clean();
            echo "error";
        }
    } else {
        ob_end_clean();
        echo "invalid_action";
    }
} catch (Exception $e) {
    error_log("Block User Error: " . $e->getMessage());
    ob_end_clean();
    echo "error";
}
?>
