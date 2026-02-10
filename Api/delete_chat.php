<?php
// delete_chat.php
include("connection.php");
session_start();
date_default_timezone_set('Asia/Kolkata');

$message_id = intval($_POST['message_id'] ?? 0);
$my = intval($_POST['my_profile_id'] ?? 0);
if(!$message_id || !$my){ echo "error"; exit; }

// delete only if I am sender
$stmt = $con->prepare("DELETE FROM tbl_messages WHERE id=? AND sender_id=?");
$stmt->bind_param("ii", $message_id, $my);
$stmt->execute();

echo ($stmt->affected_rows>0) ? "ok" : "error";
