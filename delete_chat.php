<?php
// delete_chat.php
include("connection.php");
session_start();
date_default_timezone_set('Asia/Kolkata');

$message_id = intval($_POST['message_id'] ?? 0);
$my = intval($_POST['my_profile_id'] ?? 0);

if(!$message_id || !$my){ echo "error"; exit; }

// Fetch message details
$q = $con->prepare("SELECT id, sender_id, receiver_id, file, deleted_by_sender, deleted_by_receiver FROM tbl_messages WHERE id=? LIMIT 1");
$q->bind_param("i", $message_id);
$q->execute();
$res = $q->get_result();

if(!$res || $res->num_rows === 0){ echo "error"; exit; }
$row = $res->fetch_assoc();

$is_sender = ($row['sender_id'] == $my);
$is_receiver = ($row['receiver_id'] == $my);

if(!$is_sender && !$is_receiver){
    // Not authorized
    echo "error"; 
    exit;
}

// Update SOFT DELETE flags
if($is_sender){
    $con->query("UPDATE tbl_messages SET deleted_by_sender=1 WHERE id=$message_id");
    $row['deleted_by_sender'] = 1; // update local var for check below
} else {
    $con->query("UPDATE tbl_messages SET deleted_by_receiver=1 WHERE id=$message_id");
    $row['deleted_by_receiver'] = 1; // update local var
}

// CHECK IF BOTH DELETED
if($row['deleted_by_sender'] == 1 && $row['deleted_by_receiver'] == 1){
    // Hard delete
    $file = $row['file'];
    
    // Delete from DB
    $con->query("DELETE FROM tbl_messages WHERE id=$message_id");

    // Remove file if exists
    if(!empty($file) && file_exists(__DIR__ . '/' . $file)){
        @unlink(__DIR__ . '/' . $file);
    }
}

echo "ok";