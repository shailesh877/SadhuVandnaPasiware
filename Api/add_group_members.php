<?php
// add_group_members.php
include 'headers.php';
include 'connection.php';

$group_id = intval($_POST['group_id'] ?? 0);
$user_id = intval($_POST['user_id'] ?? 0); // The user doing the adding
$member_ids = $_POST['member_ids'] ?? ''; // comma separated user IDs

if (!$group_id || !$user_id || empty($member_ids)) {
    echo json_encode(["status" => "error", "message" => "Group ID, User ID and Member IDs are required"]);
    exit;
}

// Security: Check if user is admin
$check = $con->query("SELECT created_by FROM tbl_groups WHERE id = $group_id");
$group = $check->fetch_assoc();

if (!$group || $group['created_by'] != $user_id) {
    echo json_encode(["status" => "error", "message" => "Only group admin can add members"]);
    exit;
}

$members = array_filter(explode(',', $member_ids));
$added_count = 0;

foreach ($members as $mem_id) {
    $m_id = intval($mem_id);
    if ($m_id > 0) {
        // Check if already a member
        $chk_mem = $con->query("SELECT id FROM tbl_group_members WHERE group_id=$group_id AND user_id=$m_id");
        if ($chk_mem->num_rows == 0) {
            $con->query("INSERT INTO tbl_group_members (group_id, user_id, role) VALUES ($group_id, $m_id, 'member')");
            $added_count++;
        }
    }
}

echo json_encode(["status" => "success", "message" => "$added_count members added successfully"]);
?>
