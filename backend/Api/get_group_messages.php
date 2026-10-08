<?php
// get_group_messages.php
include 'headers.php';
include 'connection.php';

$group_id = intval($_GET['group_id'] ?? 0);
$user_id = intval($_GET['user_id'] ?? 0); // To track who is requesting and maybe mark as seen
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 30;
$before_time = isset($_GET['before_time']) ? $con->real_escape_string($_GET['before_time']) : '';
$before_id = isset($_GET['before_id']) ? intval($_GET['before_id']) : 0;
$since_time = isset($_GET['since_time']) ? $con->real_escape_string($_GET['since_time']) : '';
$since_id = isset($_GET['since_id']) ? intval($_GET['since_id']) : 0;

if (!$group_id) {
    echo json_encode(["status" => "error", "message" => "Group ID is required"]);
    exit;
}

// Auto-migration: add is_deleted if it doesn't exist
$check_del = $con->query("SHOW COLUMNS FROM `tbl_group_messages` LIKE 'is_deleted'");
if ($check_del->num_rows == 0) {
    $con->query("ALTER TABLE `tbl_group_messages` ADD COLUMN `is_deleted` TINYINT(1) DEFAULT 0");
}

$cursor_condition = "";
if ($before_time && $before_id) {
    $cursor_condition = " AND (gm.created_at < '{$before_time}' OR (gm.created_at = '{$before_time}' AND gm.id < {$before_id})) ";
} elseif ($before_time) {
    $cursor_condition = " AND gm.created_at < '{$before_time}' ";
} elseif ($since_time && $since_id) {
    $cursor_condition = " AND (gm.created_at > '{$since_time}' OR (gm.created_at = '{$since_time}' AND gm.id > {$since_id})) ";
} elseif ($since_time) {
    $cursor_condition = " AND gm.created_at > '{$since_time}' ";
}

$sql = "
    SELECT 
        gm.*, 
        gm.client_message_id,
        m.name as sender_name, 
        m.profile_photo as sender_photo 
    FROM tbl_group_messages gm
    LEFT JOIN tbl_members m ON gm.sender_id = m.id
    WHERE gm.group_id = $group_id
    {$cursor_condition}
    ORDER BY gm.created_at DESC, gm.id DESC
    LIMIT $limit
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

$seenMsgIds = [];
while ($row = $res->fetch_assoc()) {
    $client_id = $row['client_message_id'] ?? $row['id'];
    $seenMsgIds[$client_id] = true;
    
    $messages[] = [
        "id" => $row['id'],
        "client_message_id" => $client_id,
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

// 3. FETCH PENDING FROM REDIS
require_once 'RedisConfig.php';
$redis = RedisConfig::getConnection();
if ($redis && empty($before_time)) {
    $redisKey = "group_chat:{$group_id}";
    $redisMessages = $redis->zRange($redisKey, 0, -1);
    
    foreach ($redisMessages as $rm) {
        $msgObj = json_decode($rm, true);
        if (!$msgObj) continue;
        
        $cid = $msgObj['client_message_id'] ?? null;
        if ($cid && !isset($seenMsgIds[$cid])) {
            $seenMsgIds[$cid] = true;
            
            // Get sender name/photo for pending (approximate or just empty until DB resolves)
            $messages[] = [
                "id" => "pending_" . $cid,
                "client_message_id" => $cid,
                "group_id" => $group_id,
                "sender_id" => (int)$msgObj['sender_id'],
                "sender_name" => "Pending", // UI can resolve locally or wait for sync
                "sender_photo" => "",
                "message" => $msgObj['message'],
                "attachment" => $msgObj['attachment'],
                "file_type" => $msgObj['file_type'],
                "is_deleted" => 0,
                "seen_by" => "[]",
                "created_at" => $msgObj['created_at']
            ];
        }
    }
}

// Sort combined messages by created_at
usort($messages, function($a, $b) {
    return strtotime($a['created_at']) - strtotime($b['created_at']);
});

// 4. Get Group Settings
$gQ = $con->query("SELECT admins_only FROM tbl_groups WHERE id = $group_id");
$group_data = $gQ->fetch_assoc();
$admins_only = $group_data ? intval($group_data['admins_only']) : 0;

echo json_encode([
    "status" => "success", 
    "data" => $messages,
    "admins_only" => $admins_only
]);
?>
