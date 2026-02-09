<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include("../php/connection.php");

// profile_id of the OTHER user (receiver)
$receiver_id = $_GET['profile_id'] ?? 0;
// my profile id (to check if they are typing to ME)
$my_id = $_GET['my_profile_id'] ?? 0;

if(!$receiver_id){
    echo json_encode(['status'=>'error', 'message'=>'Missing profile_id']);
    exit;
}

// 1. Get Receiver Status
// considering online if active in last 2 minutes
$sql = "SELECT last_active, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(last_active) < 120) as is_online 
        FROM tbl_marriage_profiles WHERE id='$receiver_id' LIMIT 1";
$res = $con->query($sql);
$row = $res->fetch_assoc();

$online = false;
$last_active = null;

if($row){
    $online = ($row['is_online'] == 1);
    if($row['last_active']){
        $last_active = date("h:i A, d M", strtotime($row['last_active']));
    }
}

// 2. Check Typing Status
// tbl_chat_typing: profile_id (who is typing), target_id (to whom), is_typing (1/0), updated_at
$is_typing = false;
if($my_id){
    $t_sql = "SELECT is_typing, updated_at FROM tbl_chat_typing 
              WHERE profile_id='$receiver_id' AND target_id='$my_id' 
              AND is_typing=1 
              AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(updated_at) < 5) 
              LIMIT 1";
    $t_res = $con->query($t_sql);
    if($t_res->num_rows > 0){
        $is_typing = true;
    }
}

echo json_encode([
    'status' => 'success',
    'online' => $online,
    'last_active' => $last_active,
    'is_typing' => $is_typing
]);
?>
