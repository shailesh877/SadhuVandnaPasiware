<?php
include 'headers.php';
include 'connection.php';
include 'push_helper.php';

$my = intval($_POST['my_profile_id'] ?? 0);
$receiver = intval($_POST['receiver_id'] ?? 0);
$msg = trim($_POST['message'] ?? '');
$platform = $_POST['platform'] ?? 'marriage';

if(!$my || !$receiver || $msg === '') { 
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
    exit; 
}

$stmt = $con->prepare("INSERT INTO tbl_messages (sender_id, receiver_id, message, chat_platform, seen, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
$stmt->bind_param("iiss", $my, $receiver, $msg, $platform);

if($stmt->execute()){
    // Send Push Notification
    $sender_name = "New Message";
    $real_user_id = 0;

    if($platform === 'community'){
        // Community: IDs are member IDs
        $sQ = $con->query("SELECT name FROM tbl_members WHERE id = $my LIMIT 1");
        if($sQ && $sRow = $sQ->fetch_assoc()) $sender_name = $sRow['name'];
        $real_user_id = $receiver; // In community, receiver_id is the user_id
    } else {
        // Marriage: IDs are marriage profile IDs
        $sQ = $con->query("SELECT name FROM tbl_marriage_profiles WHERE id = $my LIMIT 1");
        if($sQ && $sRow = $sQ->fetch_assoc()) $sender_name = $sRow['name'];

        $rQ = $con->query("SELECT user_id FROM tbl_marriage_profiles WHERE id = $receiver LIMIT 1");
        if($rQ && $rRow = $rQ->fetch_assoc()){
            $real_user_id = $rRow['user_id'];
        }
    }

    if($real_user_id > 0){
        sendExpoPushNotification($con, $real_user_id, $sender_name, $msg, [
            "type" => "chat",
            "sender_profile_id" => $my,
            "platform" => $platform
        ]);
    }

    echo json_encode(["status" => "success", "message" => "Sent"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed"]);
}
?>
