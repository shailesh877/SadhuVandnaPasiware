<?php
include 'headers.php';
include 'connection.php';

// Handle JSON Input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$group_id = intval($_REQUEST['group_id'] ?? $data['group_id'] ?? 0);
$user_id = intval($_REQUEST['user_id'] ?? $data['user_id'] ?? 0);
$admin_id = intval($_REQUEST['admin_id'] ?? $data['admin_id'] ?? 0);

if(!$group_id || !$user_id || !$admin_id){ 
    echo json_encode(["status" => "error", "message" => "Invalid ID"]);
    exit; 
}

try {
    // 1. Verify if the requester is an admin of the group
    $admin_check = $con->query("SELECT id FROM tbl_group_members WHERE group_id = $group_id AND user_id = $admin_id AND role = 'admin'");
    
    if ($admin_check->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Unauthorized. Only admins can remove members."]);
        exit;
    }

    // 2. Cannot remove the last admin or yourself if you want to leave (leave is different, but for now let's prevent self removal here)
    if ($user_id == $admin_id) {
        echo json_encode(["status" => "error", "message" => "You cannot remove yourself. Use 'Leave Group' instead."]);
        exit;
    }

    // 3. Delete the member
    $stmt = $con->prepare("DELETE FROM tbl_group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();

    if($stmt->affected_rows > 0){
        echo json_encode(["status" => "success", "message" => "Member removed successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Member not found in this group."]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
