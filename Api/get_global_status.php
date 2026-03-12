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

$my_profile_id = 0;
if ($mpQ && $mpQ->num_rows > 0) {
    $my_profile_id = $mpQ->fetch_assoc()['id'];
    $response['my_profile_id'] = $my_profile_id;
}


// 1️⃣ Unread messages count (Community)
$q_msgs_comm = $con->query("
    SELECT COUNT(*) 
    FROM tbl_messages 
    WHERE receiver_id = '$user_id' 
    AND chat_platform = 'community'
    AND seen = 0
");
$unread_comm = ($q_msgs_comm) ? intval($q_msgs_comm->fetch_row()[0]) : 0;

$unread_marriage_msgs = 0;
$unread_proposals = 0;

if ($my_profile_id) {
    // 2️⃣ Unread messages count (Marriage)
    $q_msgs_marr = $con->query("
        SELECT COUNT(*) 
        FROM tbl_messages 
        WHERE receiver_id = '$my_profile_id' 
        AND chat_platform = 'marriage'
        AND seen = 0
    ");
    $unread_marriage_msgs = ($q_msgs_marr) ? intval($q_msgs_marr->fetch_row()[0]) : 0;

    // 3️⃣ Pending matrimony requests count
    $q_reqs = $con->query("
        SELECT COUNT(*) 
        FROM tbl_proposals 
        WHERE receiver_id = '$my_profile_id' 
        AND status = 'pending'
    ");
    $unread_proposals = ($q_reqs) ? intval($q_reqs->fetch_row()[0]) : 0;
}

$response['unread_count'] = $unread_comm + $unread_marriage_msgs + $unread_proposals;


// Check if platform column exists
$checkPlat = $con->query("SHOW COLUMNS FROM tbl_calls LIKE 'platform'");
$hasPlatform = ($checkPlat && $checkPlat->num_rows > 0);

// 2️⃣ Incoming call (last 30 sec, ringing)
// Check both: Community (receiver_id = user_id) AND Marriage (receiver_id = my_profile_id)
if ($hasPlatform) {
    $whereCall = "(receiver_id = '$user_id' AND platform = 'community')";
    if ($my_profile_id) {
        $whereCall .= " OR (receiver_id = '$my_profile_id' AND platform = 'marriage')";
    }
} else {
    // Fallback to old logic (only marriage) or just match by receiver_id if we can't distinguish
    $whereCall = "receiver_id = '$my_profile_id'";
}

$inc = $con->query("
    SELECT * 
    FROM tbl_calls 
    WHERE ($whereCall)
    AND status = 'ringing' 
    AND created_at > (NOW() - INTERVAL 30 SECOND)
    ORDER BY id DESC 
    LIMIT 1
");

if ($inc && $inc->num_rows > 0) {
    $call = $inc->fetch_assoc();
    $platform = $call['platform'] ?? 'marriage';
    $caller_name = "Unknown";
    $caller_photo = "images/logo.png";

    if ($platform == 'marriage') {
        $c_info = $con->query("
            SELECT full_name, photo 
            FROM tbl_marriage_profiles 
            WHERE id = '".$call['caller_id']."' 
            LIMIT 1
        ")->fetch_assoc();
        if ($c_info) {
            $caller_name  = $c_info['full_name'];
            $caller_photo = !empty($c_info['photo']) ? "uploads/photo/".$c_info['photo'] : "images/logo.png";
        }
    } else {
        // Community
        $c_info = $con->query("
            SELECT name, profile_photo 
            FROM tbl_members 
            WHERE id = '".$call['caller_id']."' 
            LIMIT 1
        ")->fetch_assoc();
        if ($c_info) {
            $caller_name  = $c_info['name'];
            $caller_photo = !empty($c_info['profile_photo']) ? $c_info['profile_photo'] : "images/logo.png";
        }
    }

    $response['incoming_call'] = [
        "call_id"       => $call['id'],
        "caller_id"     => $call['caller_id'],
        "caller_name"   => $caller_name,
        "caller_photo"  => $caller_photo,
        "type"          => $call['type'],
        "platform"      => $platform,
        "peer_id"       => $call['caller_peer_id']
    ];
}

echo json_encode($response);
?>
