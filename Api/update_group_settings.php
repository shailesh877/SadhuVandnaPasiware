<?php
include 'headers.php';
include 'connection.php';

$group_id = intval($_POST['group_id'] ?? 0);
$user_id = intval($_POST['user_id'] ?? 0);
$admins_only = intval($_POST['admins_only'] ?? 0);

if (!$group_id || !$user_id) {
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
    exit;
}

// 1. Check if user is admin
$gQ = $con->query("SELECT created_by FROM tbl_groups WHERE id = $group_id");
$group = $gQ->fetch_assoc();

$is_admin = ($group && $group['created_by'] == $user_id);
if (!$is_admin) {
    $mQ = $con->query("SELECT role FROM tbl_group_members WHERE group_id = $group_id AND user_id = $user_id");
    $member = $mQ->fetch_assoc();
    if ($member && $member['role'] === 'admin') {
        $is_admin = true;
    }
}

if (!$is_admin) {
    echo json_encode(["status" => "error", "message" => "Only admins can change settings"]);
    exit;
}

// 2. Update settings
$stmt = $con->prepare("UPDATE tbl_groups SET admins_only = ? WHERE id = ?");
$stmt->bind_param("ii", $admins_only, $group_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Settings updated"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update: " . $stmt->error]);
}
?>
