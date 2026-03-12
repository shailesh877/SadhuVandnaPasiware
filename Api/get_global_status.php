<?php
// get_global_status.php
error_reporting(0);
include("connection.php");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Content-Type: application/json");
date_default_timezone_set("Asia/Kolkata");

$response = [
    "status" => true,
    "unread_count" => 0,
    "incoming_call" => null,
    "my_profile_id" => null
];

// RN se user_id aayega
$user_id = $_POST['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode($response);
    exit;
}

// Get my profile id
$mpQ = $con->query("
    SELECT id 
    FROM tbl_marriage_profiles 
    WHERE user_id = '$user_id' 
    LIMIT 1
");

if ($mpQ->num_rows == 0) {
    echo json_encode($response);
    exit;
}

$my_profile_id = $mpQ->fetch_assoc()['id'];
$response['my_profile_id'] = $my_profile_id;


// 1️⃣ Unread messages count
$q_msgs = $con->query("
    SELECT COUNT(*) 
    FROM tbl_messages 
    WHERE receiver_id = '$my_profile_id' 
    AND seen = 0
");
$unread_messages = intval($q_msgs->fetch_row()[0]);

// 2️⃣ Pending matrimony requests count
$q_reqs = $con->query("
    SELECT COUNT(*) 
    FROM tbl_proposals 
    WHERE receiver_id = '$my_profile_id' 
    AND status = 'pending'
");
$unread_proposals = intval($q_reqs->fetch_row()[0]);

$response['unread_count'] = $unread_messages + $unread_proposals;


// 2️⃣ Incoming call (last 30 sec, ringing)
$inc = $con->query("
    SELECT * 
    FROM tbl_calls 
    WHERE receiver_id = '$my_profile_id' 
    AND status = 'ringing' 
    AND created_at > (NOW() - INTERVAL 30 SECOND)
    ORDER BY id DESC 
    LIMIT 1
");

if ($inc && $inc->num_rows > 0) {

    $call = $inc->fetch_assoc();

    $c_info = $con->query("
        SELECT full_name, photo 
        FROM tbl_marriage_profiles 
        WHERE id = '".$call['caller_id']."' 
        LIMIT 1
    ")->fetch_assoc();

    $response['incoming_call'] = [
        "call_id"       => $call['id'],
        "caller_id"     => $call['caller_id'],
        "caller_name"   => $c_info['full_name'] ?? "Unknown",
        "caller_photo"  => !empty($c_info['photo'])
                            ? "uploads/photo/".$c_info['photo']
                            : "images/logo.png",
        "type"          => $call['type'],
        "peer_id"       => $call['caller_peer_id']
    ];
}

echo json_encode($response);
?>
