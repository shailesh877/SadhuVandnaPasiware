<?php
include 'headers.php';
include 'connection.php';

$user_id = intval($_POST['user_id'] ?? 0);
$group_name = $con->real_escape_string($_POST['group_name'] ?? '');
$description = $con->real_escape_string($_POST['description'] ?? '');
$platform = $con->real_escape_string($_POST['platform'] ?? 'community');
$member_ids = $_POST['member_ids'] ?? ''; // expects comma separated user IDs

if (!$user_id || empty($group_name)) {
    echo json_encode(["status" => "error", "message" => "User ID and Group Name are required"]);
    exit;
}

// Ensure the creator is in the member list
$members = array_filter(explode(',', $member_ids));
if (!in_array($user_id, $members)) {
    $members[] = $user_id;
}

// 1. Insert into tbl_groups
$sql = "INSERT INTO tbl_groups (name, description, created_by, platform) VALUES ('$group_name', '$description', $user_id, '$platform')";
if ($con->query($sql)) {
    $group_id = $con->insert_id;

    // 2. Insert into tbl_group_members
    foreach ($members as $mem_id) {
        $m_id = intval($mem_id);
        if ($m_id > 0) {
            $role = ($m_id === $user_id) ? 'admin' : 'member';
            $con->query("INSERT INTO tbl_group_members (group_id, user_id, role) VALUES ($group_id, $m_id, '$role')");
        }
    }

    echo json_encode(["status" => "success", "message" => "Group created successfully", "group_id" => $group_id]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to create group: " . $con->error]);
}
?>
