<?php
// send_group_message.php
include 'headers.php';
include 'connection.php';
include 'push_helper.php';

$group_id = intval($_POST['group_id'] ?? 0);
$sender_id = intval($_POST['sender_id'] ?? 0);
$message = $con->real_escape_string($_POST['message'] ?? '');
$attachment = '';
$file_type = null;

// Handle file upload if any
if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $target_dir = "../uploads/chat/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        $attachment = "/uploads/chat/" . $new_filename;
        $mime = $_FILES['file']['type'];
        if (strpos($mime, 'video') !== false) $file_type = 'video';
        else if (strpos($mime, 'image') !== false) $file_type = 'image';
        else $file_type = 'document';
    }
} elseif (isset($_POST['forward_file']) && !empty($_POST['forward_file'])) {
    $attachment = $_POST['forward_file'];
    $file_type = $_POST['file_type'] ?? 'image';
}

// Auto-migration check: add file_type if it doesn't exist
$check_col = $con->query("SHOW COLUMNS FROM `tbl_group_messages` LIKE 'file_type'");
if ($check_col->num_rows == 0) {
    $con->query("ALTER TABLE `tbl_group_messages` ADD COLUMN `file_type` VARCHAR(50) DEFAULT NULL");
}

if (!$group_id || !$sender_id || (empty($message) && empty($attachment))) {
    echo json_encode(["status" => "error", "message" => "Group ID, Sender ID, and Message or Attachment are required"]);
    exit;
}

// Auto-migration check: add admins_only if it doesn't exist
$check_adm = $con->query("SHOW COLUMNS FROM `tbl_groups` LIKE 'admins_only'");
if ($check_adm->num_rows == 0) {
    $con->query("ALTER TABLE `tbl_groups` ADD COLUMN `admins_only` TINYINT(1) DEFAULT 0");
}

// Check Group Permissions
$gQ = $con->query("SELECT created_by, admins_only FROM tbl_groups WHERE id = $group_id");
$group = $gQ->fetch_assoc();

if ($group && $group['admins_only'] == 1) {
    // Check if sender is admin or creator
    $is_admin = ($group['created_by'] == $sender_id);
    if (!$is_admin) {
        $mQ = $con->query("SELECT role FROM tbl_group_members WHERE group_id = $group_id AND user_id = $sender_id");
        $member = $mQ->fetch_assoc();
        if ($member && $member['role'] === 'admin') {
            $is_admin = true;
        }
    }

    if (!$is_admin) {
        echo json_encode(["status" => "error", "message" => "Only admins can send messages to this group"]);
        exit;
    }
}

$client_message_id = $_POST['client_message_id'] ?? uniqid('gmsg_', true);

require_once 'RedisConfig.php';
require_once 'QueueManager.php';
$redis = RedisConfig::getConnection();

if ($redis) {
    // 1. Build message object
    $chatObj = [
        "client_message_id" => $client_message_id,
        "group_id" => $group_id,
        "sender_id" => $sender_id,
        "message" => $message,
        "attachment" => $attachment,
        "file_type" => $file_type,
        "created_at" => date('Y-m-d H:i:s')
    ];

    // 2. Add to Redis (TTL 7 days)
    $redisKey = "group_chat:{$group_id}";
    $redis->zAdd($redisKey, microtime(true), json_encode($chatObj));
    $redis->expire($redisKey, 7 * 86400);

    // 3. Push to Queue
    QueueManager::pushJob('sync_group_chat', $chatObj, "gchat_{$client_message_id}", 600);

    // 4. WebSocket Broadcast
    $url = 'http://localhost:3000/api/trigger';
    
    $groupNameQ = $con->query("SELECT name FROM tbl_groups WHERE id = '$group_id' LIMIT 1");
    $groupName = ($groupNameQ && $gRow = $groupNameQ->fetch_assoc()) ? $gRow['name'] : "Group";

    $postData = array(
        'room' => "group_{$group_id}",
        'type' => 'new_group_notification',
        'payload' => [
            'type' => 'group_chat',
            'group_id' => $group_id,
            'group_name' => $groupName,
            'message' => $chatObj
        ]
    );
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($postData)
        )
    );
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);

    // 5. Push Notification Job
    $redis->lPush(QueueManager::QUEUE_NOTIFICATIONS, json_encode([
        'type' => 'group_chat',
        'sender_id' => $sender_id,
        'group_id' => $group_id,
        'message' => $message,
        'attachment' => $attachment
    ]));

    echo json_encode(["status" => "success", "message" => "Message sent successfully", "client_message_id" => $client_message_id]);
    exit;
} else {
    // MySQL Fallback
    $sql = "INSERT IGNORE INTO tbl_group_messages (client_message_id, group_id, sender_id, message, attachment, file_type) VALUES ('$client_message_id', $group_id, $sender_id, '$message', '$attachment', '$file_type')";

    if ($con->query($sql)) {
        // Fallback Notifications
        $redis = RedisConfig::getConnection();
        if ($redis) {
            $redis->lPush(QueueManager::QUEUE_NOTIFICATIONS, json_encode([
                'type' => 'group_chat',
                'sender_id' => $sender_id,
                'group_id' => $group_id,
                'message' => $message,
                'attachment' => $attachment
            ]));
        }
        echo json_encode(["status" => "success", "message" => "Message sent successfully", "client_message_id" => $client_message_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to send message: " . $con->error]);
    }
}
?>
