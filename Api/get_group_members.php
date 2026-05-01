<?php
// get_group_members.php
include 'headers.php';
include 'connection.php';

$group_id = intval($_GET['group_id'] ?? 0);

if (!$group_id) {
    echo json_encode(["status" => "error", "message" => "Group ID is required"]);
    exit;
}

// Assume platform is community for now as groups are built for it
$sql = "
    SELECT gm.id as membership_id, gm.role, gm.joined_at, m.id as user_id, m.name, m.profile_photo, (m.last_active >= NOW() - INTERVAL 5 MINUTE) as is_online 
    FROM tbl_group_members gm
    INNER JOIN tbl_members m ON gm.user_id = m.id
    WHERE gm.group_id = $group_id
    ORDER BY gm.role ASC, m.name ASC
";

$gQ = $con->query("SELECT created_by FROM tbl_groups WHERE id = $group_id");
$group = $gQ->fetch_assoc();
$creator_id = $group ? intval($group['created_by']) : 0;

$res = $con->query($sql);
$members = [];

while ($row = $res->fetch_assoc()) {
    $members[] = [
        "user_id" => $row['user_id'],
        "name" => $row['name'],
        "profile_photo" => $row['profile_photo'],
        "role" => $row['role'],
        "joined_at" => $row['joined_at'],
        "is_online" => ($row['is_online'] == 1)
    ];
}

echo json_encode(["status" => "success", "data" => $members, "creator_id" => $creator_id]);
?>
