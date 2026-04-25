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
    $is_group = (isset($_REQUEST['is_group']) && $_REQUEST['is_group'] == '1') || (isset($data['is_group']) && $data['is_group'] == '1');
    
    if ($is_group) {
        $stmt = $con->prepare("UPDATE tbl_group_messages SET is_deleted = 1, message = '🚫 This message was deleted', attachment = '' WHERE id=? AND sender_id=?");
    } else {
        $stmt = $con->prepare("UPDATE tbl_messages SET is_deleted = 1, message = '🚫 This message was deleted', file = '', file_type = NULL WHERE id=? AND sender_id=?");
    }
    
    $stmt->bind_param("ii", $message_id, $my);
    $stmt->execute();

    if($stmt->affected_rows > 0){
        echo json_encode(["status" => "success", "message" => "Deleted"]);
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "Failed or Not Authorized",
            "debug" => [
                "message_id" => $message_id,
                "sender_id" => $my,
                "is_group" => $is_group,
                "affected" => $stmt->affected_rows
            ]
        ]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
