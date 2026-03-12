<?php
include("connection.php");
include("push_helper.php");
header('Content-Type: application/json');

date_default_timezone_set('Asia/Kolkata');

$caller_id   = $_POST['caller_id']   ?? 0;
$receiver_id = $_POST['receiver_id'] ?? 0;
$type        = $_POST['type']        ?? 'video';
$peer_id     = $_POST['peer_id']     ?? '';

if (!$caller_id || !$receiver_id || !$peer_id) {
    echo json_encode([
        "status" => false,
        "message" => "Missing required parameters"
    ]);
    exit;
}

// End previous ringing calls (avoid duplicate)
$con->query("
    UPDATE tbl_calls 
    SET status='ended' 
    WHERE caller_id='$caller_id' 
    AND receiver_id='$receiver_id' 
    AND status='ringing'
");

$stmt = $con->prepare("
    INSERT INTO tbl_calls 
    (caller_id, receiver_id, type, status, caller_peer_id) 
    VALUES (?, ?, ?, 'ringing', ?)
");

$stmt->bind_param("iiss", $caller_id, $receiver_id, $type, $peer_id);

if ($stmt->execute()) {
    // Send Push Notification
    // 1. Get User ID from Profile ID (receiver_id)
    $uQ = $con->query("SELECT user_id FROM tbl_marriage_profiles WHERE id = '$receiver_id' LIMIT 1");
    if ($uQ && $uQ->num_rows > 0) {
        $r_user_id = $uQ->fetch_assoc()['user_id'];
        
        sendExpoPushNotification($con, $r_user_id, "Incoming Call", "Incoming call...", [
            "channelId" => $peer_id,
            "caller_id" => $caller_id,
            "type" => $type,
            "is_call" => true
        ]);
    }

    echo json_encode([
        "status" => true,
        "call_id" => $con->insert_id,
        "message" => "Call initiated"
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Call failed"
    ]);
}
?>
