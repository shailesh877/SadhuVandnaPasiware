<?php
include 'headers.php';
include 'connection.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$invite_code = $con->real_escape_string($data['invite_code'] ?? '');
$user_id = intval($data['user_id'] ?? 0);

if (!$invite_code || !$user_id) {
    echo json_encode(["status" => "error", "message" => "Invite code and User ID are required"]);
    exit;
}

// 1. Find the group
$res = $con->query("SELECT id, name FROM tbl_groups WHERE invite_code = '$invite_code'");
$group = $res->fetch_assoc();

if (!$group) {
    echo json_encode(["status" => "error", "message" => "Invalid invite link."]);
    exit;
}

$group_id = $group['id'];

// 2. Check if already a member
$check = $con->query("SELECT id FROM tbl_group_members WHERE group_id = $group_id AND user_id = $user_id");
if ($check->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "You are already a member of this group.", "group" => $group]);
    exit;
}

// 3. Join the group
$sql = "INSERT INTO tbl_group_members (group_id, user_id, role) VALUES ($group_id, $user_id, 'member')";
if ($con->query($sql)) {
    echo json_encode(["status" => "success", "message" => "Successfully joined the group!", "group" => $group]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to join group: " . $con->error]);
}
?>
