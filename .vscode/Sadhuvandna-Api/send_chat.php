<?php
include 'headers.php';
include 'connection.php';

$sender_id = $_POST['sender_id'] ?? ''; // marriage_profile_id
$receiver_id = $_POST['receiver_id'] ?? ''; // marriage_profile_id or user_id? Original uses profile_id
$message = $_POST['message'] ?? '';

if(!$sender_id || !$receiver_id || !$message){
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

date_default_timezone_set('Asia/Kolkata');
$date = date('d-m-Y H:i:s');

// TODO: Validate payment here if needed same as message.php

// Insert
$stmt = $con->prepare("INSERT INTO tbl_chats (sender_id, receiver_id, message, date) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $sender_id, $receiver_id, $message, $date);

if($stmt->execute()){
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to send"]);
}
?>
