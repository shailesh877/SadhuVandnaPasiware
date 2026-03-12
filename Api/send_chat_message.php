<?php
include 'headers.php';
include 'connection.php';
include 'push_helper.php';

$my = intval($_POST['my_profile_id'] ?? 0);
$receiver = intval($_POST['receiver_id'] ?? 0);
$msg = trim($_POST['message'] ?? '');

if(!$my || !$receiver || $msg === '') { 
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
    exit; 
}

$stmt = $con->prepare("INSERT INTO tbl_messages (sender_id, receiver_id, message, seen, created_at) VALUES (?, ?, ?, 0, NOW())");
$stmt->bind_param("iis", $my, $receiver, $msg);

if($stmt->execute()){
    // Send Push Notification
    $sender_name = "New Message";
    $sQ = $con->query("SELECT name FROM tbl_marriage_profiles WHERE id = $my LIMIT 1");
    if($sQ && $sRow = $sQ->fetch_assoc()) $sender_name = $sRow['name'];

    $rQ = $con->query("SELECT user_id FROM tbl_marriage_profiles WHERE id = $receiver LIMIT 1");
    if($rQ && $rRow = $rQ->fetch_assoc()){
        $real_user_id = $rRow['user_id'];
        sendExpoPushNotification($con, $real_user_id, $sender_name, $msg, [
            "type" => "chat",
            "sender_profile_id" => $my
        ]);
    }

    echo json_encode(["status" => "success", "message" => "Sent"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed"]);
}
?>
