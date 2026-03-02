<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
include("connection.php");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SESSION me mobile store hai
$user_mobile = $_SESSION['sadhu_user_id'] ?? '';

if(!$con || !$user_mobile){
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['unread_count' => 0, 'msg_count' => 0]);
    exit;
}

// Step 1: Get user_id from mobile
$userQ = $con->query("SELECT id FROM tbl_members WHERE mobile = '$user_mobile' LIMIT 1");

if($userQ->num_rows == 0){
    echo json_encode(['unread_count' => 0, 'msg_count' => 0]);
    exit;
}

$user_id = $userQ->fetch_assoc()['id'];

// Get marriage_profile_id
$mpQ = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id = '$user_id' LIMIT 1");
$my_marriage_id = ($mpQ->num_rows > 0) ? $mpQ->fetch_assoc()['id'] : 0;

// Count community messages specifically
$c_msg = $con->query("SELECT COUNT(*) FROM tbl_messages WHERE receiver_id = '$user_id' AND chat_platform = 'community' AND seen = 0")->fetch_row()[0];

// Count everything (Messages + Proposals + Follows - or just total notifications)
$q = $con->query("
    SELECT (
        SELECT COUNT(*) FROM tbl_messages 
        WHERE receiver_id = '$my_marriage_id' AND chat_platform = 'marriage' AND seen = 0
    ) + (
        SELECT COUNT(*) FROM tbl_proposals 
        WHERE receiver_id = '$my_marriage_id' AND status = 'pending'
    ) + (
        SELECT COUNT(*) FROM tbl_followers 
        WHERE following_id = '$user_id' AND status = 'pending'
    ) as total
");

header('Content-Type: application/json');
ob_get_clean();
echo json_encode([
    'unread_count' => ( ($q) ? intval($q->fetch_row()[0]) : 0 ) + intval($c_msg),
    'msg_count' => intval($c_msg)
]);
exit;
