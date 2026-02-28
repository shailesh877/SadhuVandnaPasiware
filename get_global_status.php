<?php
// get_global_status.php
error_reporting(0);
include("connection.php");
session_start();
date_default_timezone_set('Asia/Kolkata');

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header('Content-Type: application/json');
ob_clean();

$user_mobile = $_SESSION['sadhu_user_id'] ?? '';
$response = ['unread_count' => 0, 'incoming_call' => null];

if(!$user_mobile){ echo json_encode($response); exit; }

// Get my IDs
$userQ = $con->query("SELECT id FROM tbl_members WHERE mobile = '$user_mobile' LIMIT 1");
if($userQ->num_rows == 0){ echo json_encode($response); exit; }
$user_id = $userQ->fetch_assoc()['id'];

// Get marriage_profile_id
$mpQ = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id = '$user_id' LIMIT 1");
$my_marriage_id = ($mpQ->num_rows > 0) ? $mpQ->fetch_assoc()['id'] : 0;

// Count community messages specifically
$c_msg = $con->query("SELECT COUNT(*) FROM tbl_messages WHERE receiver_id = '$user_id' AND chat_platform = 'community' AND seen = 0")->fetch_row()[0];

// 1. Unread count (Messages + Proposals + Follows)
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
$response['unread_count'] = intval($q->fetch_row()[0]) + intval($c_msg);
$response['msg_count'] = intval($c_msg);

// Auto-cleanup expired ringing calls
$old_calls = $con->query("SELECT id, caller_id, receiver_id, type, chat_platform FROM tbl_calls WHERE status='ringing' AND created_at <= (NOW() - INTERVAL 45 SECOND)");
if ($old_calls && $old_calls->num_rows > 0) {
    while($c = $old_calls->fetch_assoc()){
        $cid = $c['id'];
        $con->query("UPDATE tbl_calls SET status='ended' WHERE id=$cid");
        
        $msg_text = "SYSTEM_CALL:❌ Missed " . ucfirst($c['type']) . " Call";
        $ins = $con->prepare("INSERT INTO tbl_messages (sender_id, receiver_id, message, chat_platform, seen) VALUES (?, ?, ?, ?, 0)");
        $ins->bind_param("iiss", $c['caller_id'], $c['receiver_id'], $msg_text, $c['chat_platform']);
        $ins->execute();
    }
}

// 2. Incoming call check
// Check both platforms
$inc = $con->query("
    SELECT * FROM tbl_calls 
    WHERE (
        (receiver_id='$my_marriage_id' AND chat_platform='marriage')
        OR
        (receiver_id='$user_id' AND chat_platform='community')
    ) 
    AND status='ringing' AND created_at > (NOW() - INTERVAL 30 SECOND) 
    ORDER BY id DESC LIMIT 1
");

if($inc && $inc->num_rows > 0){
    $call = $inc->fetch_assoc();
    if($call['chat_platform'] === 'community'){
        $c_info = $con->query("SELECT name as full_name, profile_photo as photo FROM tbl_members WHERE id='".$call['caller_id']."' LIMIT 1")->fetch_assoc();
    } else {
        $c_info = $con->query("SELECT full_name, photo FROM tbl_marriage_profiles WHERE id='".$call['caller_id']."' LIMIT 1")->fetch_assoc();
    }
    
    $response['incoming_call'] = [
        'call_id' => $call['id'],
        'caller_id' => $call['caller_id'],
        'caller_name' => $c_info['full_name'] ?? 'Unknown',
        'caller_photo' => !empty($c_info['photo']) ? (strpos($c_info['photo'], 'http') === 0 ? $c_info['photo'] : "uploads/photo/".$c_info['photo']) : "images/logo.png",
        'type' => $call['type'],
        'platform' => $call['chat_platform']
    ];
}

echo json_encode($response);
?>
