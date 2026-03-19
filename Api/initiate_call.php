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
    // 0. Ensure platform column exists (Compatible way)
    $check_col = $con->query("SHOW COLUMNS FROM tbl_calls LIKE 'platform'");
    if ($check_col && $check_col->num_rows == 0) {
        $con->query("ALTER TABLE tbl_calls ADD COLUMN platform VARCHAR(20) DEFAULT 'marriage'");
    }

    // 1. Insert Call Record (So get_global_status can find it)
    $stmt = $con->prepare("INSERT INTO tbl_calls (caller_id, receiver_id, type, platform, status, caller_peer_id, created_at) VALUES (?, ?, ?, ?, 'ringing', ?, NOW())");
    $stmt->bind_param("iisss", $caller_id, $receiver_id, $type, $platform, $peer_id);
    $stmt->execute();
    $call_id = $stmt->insert_id;

    // 2. Get Receiver FCM Token
    // If marriage, receiver_id is Profile ID. In community, it's Member ID.
    if ($platform === 'marriage') {
        $target_member = $con->query("
            SELECT m.fcm_token 
            FROM tbl_members m 
            JOIN tbl_marriage_profiles mp ON mp.user_id = m.id 
            WHERE mp.id = '$receiver_id'
        ")->fetch_assoc();
    } else {
        $target_member = $con->query("SELECT fcm_token FROM tbl_members WHERE id = '$receiver_id'")->fetch_assoc();
    }

    $token = $target_member['fcm_token'] ?? null;

    // 3. Get Caller Name
    if ($platform === 'marriage') {
        $caller = $con->query("SELECT full_name FROM tbl_marriage_profiles WHERE id='$caller_id'")->fetch_assoc();
        $caller_name = $caller['full_name'] ?? "Someone";
    } else {
        $caller = $con->query("SELECT name FROM tbl_members WHERE id='$caller_id'")->fetch_assoc();
        $caller_name = $caller['name'] ?? "Someone";
    }

    if($token){
        $push_data = [
            "type" => "incoming_call",
            "call_id" => (string)$call_id,
            "caller_id" => (string)$caller_id,
            "caller_name" => $caller_name,
            "call_type" => $type,
            "channel_id" => $peer_id,
            "platform" => $platform
        ];
        sendExpoPushNotification($con, $receiver_id, "Incoming $type call", "From $caller_name", $push_data);
    }

    echo json_encode(["status" => "success", "message" => "Call Signal Sent", "call_id" => $call_id]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
