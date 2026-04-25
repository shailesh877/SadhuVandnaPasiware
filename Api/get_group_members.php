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
    SELECT gm.id as membership_id, gm.role, gm.joined_at, m.id as user_id, m.name, m.profile_photo 
    FROM tbl_group_members gm
    INNER JOIN tbl_members m ON gm.user_id = m.id
    WHERE gm.group_id = $group_id
    ORDER BY gm.role ASC, m.name ASC
";

$res = $con->query($sql);
$members = [];

while ($row = $res->fetch_assoc()) {
    $members[] = [
        "user_id" => $row['user_id'],
        "name" => $row['name'],
        "profile_photo" => $row['profile_photo'],
        "role" => $row['role'],
        "joined_at" => $row['joined_at']
    ];
}

echo json_encode(["status" => "success", "data" => $members]);
?>
