<?php
include 'headers.php';
include 'connection.php';

$message_id = intval($_POST['message_id'] ?? 0);
$my = intval($_POST['my_profile_id'] ?? 0);

if(!$message_id || !$my){ 
    echo json_encode(["status" => "error", "message" => "Invalid ID"]);
    exit; 
}

// delete only if I am sender
$stmt = $con->prepare("DELETE FROM tbl_messages WHERE id=? AND sender_id=?");
$stmt->bind_param("ii", $message_id, $my);
$stmt->execute();

if($stmt->affected_rows > 0){
    echo json_encode(["status" => "success", "message" => "Deleted"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed or Not Authorized"]);
}
?>
