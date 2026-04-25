<?php
// get_group_typing.php
include 'headers.php';
include 'connection.php';

$group_id = intval($_GET['group_id'] ?? 0);
$user_id  = intval($_GET['user_id'] ?? 0);

if (!$group_id) {
    echo json_encode(["status" => "error", "message" => "Group ID required"]);
    exit;
}

// Find anyone in this group who is currently typing (within last 5 seconds), excluding the caller
$sql = "
    SELECT gt.user_id, m.name as user_name
    FROM tbl_group_typing gt
    LEFT JOIN tbl_members m ON gt.user_id = m.id
    WHERE gt.group_id = $group_id
      AND gt.user_id != $user_id
      AND gt.is_typing = 1
      AND gt.updated_at >= NOW() - INTERVAL 5 SECOND
    LIMIT 1
";

$res = $con->query($sql);
if ($res && $row = $res->fetch_assoc()) {
    echo json_encode([
        "status" => "success",
        "typing_user" => $row['user_name']
    ]);
} else {
    echo json_encode(["status" => "success", "typing_user" => null]);
}
?>
