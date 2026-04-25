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

// Auto-migration: add is_deleted if it doesn't exist
$check_del = $con->query("SHOW COLUMNS FROM `tbl_group_messages` LIKE 'is_deleted'");
if ($check_del->num_rows == 0) {
    $con->query("ALTER TABLE `tbl_group_messages` ADD COLUMN `is_deleted` TINYINT(1) DEFAULT 0");
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

if ($user_id > 0) {
    $now = date('Y-m-d H:i:s');
    // Fetch messages to check seen status
    $check_sql = "SELECT id, seen_by FROM tbl_group_messages WHERE group_id = $group_id AND sender_id != $user_id";
    $check_res = $con->query($check_sql);
    while ($check_row = $check_res->fetch_assoc()) {
        $msg_id = $check_row['id'];
        $seen_by_json = $check_row['seen_by'] ? json_decode($check_row['seen_by'], true) : [];
        if (!is_array($seen_by_json)) $seen_by_json = [];
        
        $already_seen = false;
        foreach ($seen_by_json as $entry) {
            if (isset($entry['u']) && $entry['u'] == $user_id) {
                $already_seen = true;
                break;
            }
        }
        
        if (!$already_seen) {
            $seen_by_json[] = ["u" => $user_id, "t" => $now];
            $new_seen_by = $con->real_escape_string(json_encode($seen_by_json));
            $con->query("UPDATE tbl_group_messages SET seen_by = '$new_seen_by' WHERE id = $msg_id");
        }
    }
    // Re-fetch to get updated seen_by if needed, but actually the select below will get them anyway
}

$res = $con->query($sql);

while ($row = $res->fetch_assoc()) {
    $messages[] = [
        "id" => $row['id'],
        "group_id" => $row['group_id'],
        "sender_id" => $row['sender_id'],
        "sender_name" => $row['sender_name'],
        "sender_photo" => $row['sender_photo'],
        "message" => $row['message'],
        "attachment" => $row['attachment'],
        "file_type" => $row['file_type'],
        "is_deleted" => intval($row['is_deleted'] ?? 0),
        "seen_by" => $row['seen_by'],
        "created_at" => $row['created_at']
    ];
}

echo json_encode(["status" => "success", "data" => $messages]);
?>
