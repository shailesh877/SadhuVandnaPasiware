<?php
// get_global_status.php
error_reporting(0);
include("connection.php");
session_start();
date_default_timezone_set('Asia/Kolkata');

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header('Content-Type: application/json');
ob_clean();

$session_email = $_SESSION['sadhu_user_id'] ?? '';
$response = ['unread_count' => 0, 'incoming_call' => null];

if(!$session_email){ echo json_encode($response); exit; }

// Get my IDs
$userQ = $con->query("SELECT id FROM tbl_members WHERE email = '$session_email' LIMIT 1");
if($userQ->num_rows == 0){ echo json_encode($response); exit; }
$user_id = $userQ->fetch_assoc()['id'];

$mpQ = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id = '$user_id' LIMIT 1");
if($mpQ->num_rows == 0){ echo json_encode($response); exit; }
$my_profile_id = $mpQ->fetch_assoc()['id'];

// 1. Unread count
$q = $con->query("SELECT COUNT(*) FROM tbl_messages WHERE receiver_id = '$my_profile_id' AND seen = 0");
$response['unread_count'] = intval($q->fetch_row()[0]);

// 2. Incoming call check (ringing and created in last 30 seconds)
$inc = $con->query("SELECT * FROM tbl_calls WHERE receiver_id='$my_profile_id' AND status='ringing' AND created_at > (NOW() - INTERVAL 30 SECOND) ORDER BY id DESC LIMIT 1");
if($inc && $inc->num_rows > 0){
    $call = $inc->fetch_assoc();
    $c_info = $con->query("SELECT full_name, photo FROM tbl_marriage_profiles WHERE id='".$call['caller_id']."' LIMIT 1")->fetch_assoc();
    $response['incoming_call'] = [
        'call_id' => $call['id'],
        'caller_id' => $call['caller_id'],
        'caller_name' => $c_info['full_name'] ?? 'Unknown',
        'caller_photo' => !empty($c_info['photo']) ? "uploads/photo/".$c_info['photo'] : "images/logo.png",
        'type' => $call['type']
    ];
}

echo json_encode($response);
?>