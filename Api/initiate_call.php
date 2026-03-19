<?php
include 'headers.php';
include 'connection.php';
include 'push_helper.php';

// Handle JSON Input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$caller_id = intval($_REQUEST['caller_id'] ?? $data['caller_id'] ?? 0);
$receiver_id = intval($_REQUEST['receiver_id'] ?? $data['receiver_id'] ?? 0);
$type = $_REQUEST['type'] ?? $data['type'] ?? 'video';
$platform = $_REQUEST['platform'] ?? $data['platform'] ?? 'marriage';
$peer_id = $_REQUEST['peer_id'] ?? $data['peer_id'] ?? '';

if(!$caller_id || !$receiver_id){
    echo json_encode(["status" => "error", "message" => "Invalid IDs"]);
    exit;
}

try {
    // 1. Get Receiver FCM Token
    $target_member = $con->query("
        SELECT m.fcm_token 
        FROM tbl_members m 
        JOIN tbl_marriage_profiles mp ON mp.user_id = m.id 
        WHERE mp.id = '$receiver_id'
    ")->fetch_assoc();

    $token = $target_member['fcm_token'] ?? null;

    // 2. Get Caller Name
    $caller = $con->query("SELECT full_name FROM tbl_marriage_profiles WHERE id='$caller_id'")->fetch_assoc();
    $caller_name = $caller['full_name'] ?? "Someone";

    if($token){
        $push_data = [
            "type" => "incoming_call",
            "caller_id" => (string)$caller_id,
            "caller_name" => $caller_name,
            "call_type" => $type,
            "channel_id" => $peer_id,
            "platform" => $platform
        ];
        sendPush($token, "Incoming $type call", "From $caller_name", $push_data);
    }

    echo json_encode(["status" => "success", "message" => "Call Signal Sent"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
