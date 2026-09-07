<?php
// send_chat.php
include("connection.php");
session_start();
date_default_timezone_set('Asia/Kolkata');

$my = intval($_POST['my_profile_id'] ?? 0);
$receiver = intval($_POST['receiver_id'] ?? 0);
$msg = trim($_POST['message'] ?? '');

if(!$my || !$receiver || $msg === '') { echo "error"; exit; }

$stmt = $con->prepare("INSERT INTO tbl_messages (sender_id, receiver_id, message, seen, created_at) VALUES (?, ?, ?, 0, NOW())");
$stmt->bind_param("iis", $my, $receiver, $msg);
$stmt->execute();

// return ok
echo "ok";
