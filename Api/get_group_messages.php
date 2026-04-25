<?php
// get_group_messages.php
include 'headers.php';
include 'connection.php';

$group_id = intval($_GET['group_id'] ?? 0);
$user_id = intval($_GET['user_id'] ?? 0); // To track who is requesting and maybe mark as seen

if (!$group_id) {
    echo json_encode(["status" => "error", "message" => "Group ID is required"]);
    exit;
}

$sql = "
    SELECT 
        gm.*, 
        m.name as sender_name, 
        m.profile_photo as sender_photo 
    FROM tbl_group_messages gm
    LEFT JOIN tbl_members m ON gm.sender_id = m.id
    WHERE gm.group_id = $group_id
    ORDER BY gm.created_at ASC
";

$res = $con->query($sql);
$messages = [];

while ($row = $res->fetch_assoc()) {
    $messages[] = [
        "id" => $row['id'],
        "group_id" => $row['group_id'],
        "sender_id" => $row['sender_id'],
        "sender_name" => $row['sender_name'],
        "sender_photo" => $row['sender_photo'],
        "message" => $row['message'],
        "attachment" => $row['attachment'],
        "created_at" => $row['created_at']
    ];
}

echo json_encode(["status" => "success", "data" => $messages]);
?>
