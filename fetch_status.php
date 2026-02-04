<?php
// fetch_status.php
error_reporting(0);
include("connection.php");
session_start();
date_default_timezone_set('Asia/Kolkata');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

ob_clean();


$profile_id = intval($_GET['profile_id'] ?? 0);
$my_profile = intval($_GET['my_profile_id'] ?? 0);
$response = ['online'=>false, 'last_active'=>null, 'is_typing'=>false];

if(!$profile_id){ echo json_encode($response); exit; }

// online check
$res = $con->query("SELECT (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(m.last_active) < 25) AS online, m.last_active FROM tbl_members m JOIN tbl_marriage_profiles mp ON mp.user_id=m.id WHERE mp.id='$profile_id' LIMIT 1");
if($res && $row = $res->fetch_assoc()){
    $response['online'] = !empty($row['online']);
    if(!$response['online'] && !empty($row['last_active'])) $response['last_active'] = date("d M, h:i A", strtotime($row['last_active']));
}

// typing check (is profile_id typing TO me?)
$typ = $con->query("SELECT is_typing FROM tbl_typing WHERE profile_id='$profile_id' AND target_profile_id='$my_profile' LIMIT 1");
if($typ && $typ->num_rows>0){
    $r = $typ->fetch_assoc();
    $response['is_typing'] = (!empty($r['is_typing'])) ? true : false;
}

// max seen id (messages I sent that they have seen)
$s = $con->query("SELECT MAX(id) as m FROM tbl_messages WHERE sender_id='$my_profile' AND receiver_id='$profile_id' AND seen=1");
if($s && $row=$s->fetch_assoc()){
    $response['max_seen_id'] = $row['m'] ?? 0;
} else {
    $response['max_seen_id'] = 0;
}

// CHECK FOR INCOMING CALLS (someone calling ME)
// We check for any ringing call where I am the receiver and it was created in the last 30 seconds
$inc = $con->query("SELECT * FROM tbl_calls WHERE receiver_id='$my_profile' AND status='ringing' AND created_at > (NOW() - INTERVAL 30 SECOND) ORDER BY id DESC LIMIT 1");
if($inc && $inc->num_rows > 0){
    $call = $inc->fetch_assoc();
    // Get caller details
    $c_info = $con->query("SELECT full_name, photo FROM tbl_marriage_profiles WHERE id='".$call['caller_id']."' LIMIT 1")->fetch_assoc();
    $response['incoming_call'] = [
        'call_id' => $call['id'],
        'caller_id' => $call['caller_id'],
        'caller_name' => $c_info['full_name'] ?? 'Unknown',
        'caller_photo' => !empty($c_info['photo']) ? "uploads/photo/".$c_info['photo'] : "images/logo.png",
        'type' => $call['type'],
        'peer_id' => $call['caller_peer_id']
    ];
}

// CHECK FOR CALL STATUS (if I am calling someone, did they accept/reject?)
// We check for any non-ringing status for my latest call created/updated recently
$my_call = $con->query("SELECT * FROM tbl_calls WHERE caller_id='$my_profile' AND status IN ('accepted', 'rejected', 'ended') AND updated_at > (NOW() - INTERVAL 10 SECOND) ORDER BY updated_at DESC LIMIT 1");
if($my_call && $my_call->num_rows > 0){
    $mc = $my_call->fetch_assoc();
    $response['call_update'] = [
        'call_id' => $mc['id'],
        'status' => $mc['status']
    ];
}

echo json_encode($response);
?>