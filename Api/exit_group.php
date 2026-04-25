<?php
include 'headers.php';
include 'connection.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$group_id = intval($data['group_id'] ?? 0);
$user_id = intval($data['user_id'] ?? 0);

if (!$group_id || !$user_id) {
    echo json_encode(["status" => "error", "message" => "Group ID and User ID are required"]);
    exit;
}

try {
    // 1. Check if the user is the last member or last admin (optional but good practice)
    // For now, let's just allow leaving.
    
    $stmt = $con->prepare("DELETE FROM tbl_group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Optionally, if no members left, delete the group? 
        // Let's keep it simple.
        echo json_encode(["status" => "success", "message" => "You have left the group."]);
    } else {
        echo json_encode(["status" => "error", "message" => "You are not a member of this group."]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
