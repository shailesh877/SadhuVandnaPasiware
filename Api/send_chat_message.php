<?php
include 'headers.php';
include 'connection.php';

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
    echo json_encode(["status" => "success", "message" => "Sent"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed"]);
}
?>
