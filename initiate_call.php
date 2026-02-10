<?php
include("connection.php");
session_start();
date_default_timezone_set('Asia/Kolkata');

$caller_id = $_POST['caller_id'] ?? 0;
$receiver_id = $_POST['receiver_id'] ?? 0;
$type = $_POST['type'] ?? 'video';
$peer_id = $_POST['peer_id'] ?? '';

if(!$caller_id || !$receiver_id || !$peer_id){
    echo "error";
    exit;
}

// Close any previous active (ringing) calls between these two to avoid duplicates
$con->query("UPDATE tbl_calls SET status='ended' WHERE caller_id='$caller_id' AND receiver_id='$receiver_id' AND status='ringing'");

$stmt = $con->prepare("INSERT INTO tbl_calls (caller_id, receiver_id, type, status, caller_peer_id) VALUES (?, ?, ?, 'ringing', ?)");
$stmt->bind_param("iiss", $caller_id, $receiver_id, $type, $peer_id);
if($stmt->execute()){
    echo $con->insert_id;
} else {
    echo "error";
}
?>