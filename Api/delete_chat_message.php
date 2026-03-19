<?php
include 'headers.php';
include 'connection.php';

// Handle JSON Input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$message_id = intval($_REQUEST['message_id'] ?? $data['message_id'] ?? 0);
$my = intval($_REQUEST['my_profile_id'] ?? $data['my_profile_id'] ?? 0);

if(!$message_id || !$my){ 
    echo json_encode(["status" => "error", "message" => "Invalid ID"]);
    exit; 
}

try {
    // delete only if I am sender
    $stmt = $con->prepare("DELETE FROM tbl_messages WHERE id=? AND sender_id=?");
    $stmt->bind_param("ii", $message_id, $my);
    $stmt->execute();

    if($stmt->affected_rows > 0){
        echo json_encode(["status" => "success", "message" => "Deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed or Not Authorized"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
