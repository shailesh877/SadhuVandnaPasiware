<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include("../php/connection.php");
date_default_timezone_set("Asia/Kolkata");
$con->query("SET time_zone = '+05:30'");

// profile_id of the OTHER user (receiver)
$receiver_id = $_GET['profile_id'] ?? 0;
$my_id = $_GET['my_profile_id'] ?? 0;
$platform = $_GET['platform'] ?? 'marriage';

if(!$receiver_id){
    echo json_encode(['status'=>'error', 'message'=>'Missing profile_id']);
    exit;
}

// 1. Get Receiver Status
// Increased threshold to 300s (5 mins) for better stability
if($platform === 'community'){
    // Community: receiver_id is member_id
    $sql = "SELECT last_active, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(last_active) < 300) as is_online 
            FROM tbl_members WHERE id='$receiver_id' LIMIT 1";
} else {
    // Marriage: receiver_id is marriage_profile_id
    $sql = "SELECT last_active, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(last_active) < 300) as is_online 
            FROM tbl_marriage_profiles WHERE id='$receiver_id' LIMIT 1";
}

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
$is_typing = false;
if($my_id){
    $t_sql = "SELECT is_typing, updated_at FROM tbl_chat_typing 
              WHERE profile_id='$receiver_id' AND target_id='$my_id' 
              AND is_typing=1 
              AND chat_platform='$platform'
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
