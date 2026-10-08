<?php
include 'headers.php';
include 'connection.php';
include 'push_helper.php';

$my = intval($_POST['my_profile_id'] ?? 0);
$receiver = intval($_POST['receiver_id'] ?? 0);
$msg = trim($_POST['message'] ?? '');
$platform = $_POST['platform'] ?? 'marriage';

$attachment = null;
$file_type = null;

if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = "../uploads/chat/";
    if (!file_exists($uploadDir))
        mkdir($uploadDir, 0755, true);

    $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
    $newName = uniqid('chat_') . '.' . $ext;
    $targetFile = $uploadDir . $newName;

    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
        $attachment = "/uploads/chat/" . $newName;
        // Determine file_type
        $mime = $_FILES['attachment']['type'];
        if (strpos($mime, 'video') !== false) {
            $file_type = 'video';
        }
        else if (strpos($mime, 'image') !== false) {
            $file_type = 'image';
        }
        else {
            $file_type = 'document';
        }
    }
} elseif (isset($_POST['forward_file']) && !empty($_POST['forward_file'])) {
    $attachment = $_POST['forward_file'];
    $file_type = $_POST['file_type'] ?? 'image';
}

if (!$my || !$receiver || ($msg === '' && !$attachment)) {
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
    exit;
}

// Server-side block check
$chk_blk = $con->prepare("SELECT id FROM tbl_blocked_users WHERE ((blocker_id=? AND blocked_id=?) OR (blocker_id=? AND blocked_id=?)) AND chat_platform=?");
if ($chk_blk) {
    $chk_blk->bind_param("iiiis", $my, $receiver, $receiver, $my, $platform);
    $chk_blk->execute();
    $chk_blk->store_result();
    if ($chk_blk->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Messaging is blocked between these users."]);
        exit;
    }
}

// Server-side payment check
if ($platform === 'marriage') {
    $is_authorized = false;
    
    // Check Messages History
    $msg_check = $con->query("
        SELECT id FROM tbl_messages
        WHERE 
            (
                (sender_id = '{$my}' AND receiver_id = '{$receiver}')
                OR
                (sender_id = '{$receiver}' AND receiver_id = '{$my}')
            )
            AND chat_platform = 'marriage'
        LIMIT 1
    ");

    if ($msg_check && $msg_check->num_rows > 0) {
        $is_authorized = true;
    } else {
        // Check Wallet
        $wallet_check = $con->query("
            SELECT id FROM tbl_wallet
            WHERE 
                (
                    (sender_id = '{$my}' AND receiver_id = '{$receiver}')
                    OR
                    (sender_id = '{$receiver}' AND receiver_id = '{$my}')
                )
                AND status = 'success'
            LIMIT 1
        ");
        if ($wallet_check && $wallet_check->num_rows > 0) {
            $is_authorized = true;
        }
    }

    if (!$is_authorized) {
        echo json_encode(["status" => "error", "message" => "Payment required to chat with this user.", "requires_payment" => true]);
        exit;
    }
}

$client_message_id = $_POST['client_message_id'] ?? uniqid('msg_', true);

require_once 'RedisConfig.php';
require_once 'QueueManager.php';

$redis = RedisConfig::getConnection();

if ($redis) {
    // 1. Build message object
    $chatObj = [
        "client_message_id" => $client_message_id,
        "sender_id" => $my,
        "receiver_id" => $receiver,
        "message" => $msg,
        "file" => $attachment,
        "file_type" => $file_type,
        "chat_platform" => $platform,
        "seen" => 0,
        "is_deleted" => 0,
        "created_at" => date('Y-m-d H:i:s')
    ];

    // 2. Determine conversation thread ID (min_max to keep it symmetrical)
    $thread_id = min($my, $receiver) . "_" . max($my, $receiver);
    $redisKey = "chat_thread:{$platform}:{$thread_id}";

    // Add to Redis (TTL 7 days)
    $redis->zAdd($redisKey, microtime(true), json_encode($chatObj));
    $redis->expire($redisKey, 7 * 86400);

    // 3. Push to Queue
    QueueManager::pushJob('sync_chat', $chatObj, "chat_{$client_message_id}", 600);

    // 4. WebSocket Broadcast
    $url = 'http://localhost:3000/api/trigger';
    
    // Determine real user IDs for WebSocket routing
    $receiver_user_id = $receiver;
    $sender_user_id = $my;
    
    if ($platform === 'marriage') {
        $qR = $con->query("SELECT user_id FROM tbl_marriage_profiles WHERE id = '$receiver' LIMIT 1");
        if ($qR && $rRow = $qR->fetch_assoc()) $receiver_user_id = $rRow['user_id'];
        
        $qS = $con->query("SELECT user_id FROM tbl_marriage_profiles WHERE id = '$my' LIMIT 1");
        if ($qS && $sRow = $qS->fetch_assoc()) $sender_user_id = $sRow['user_id'];
    }

    $senderNameQ = $con->query("SELECT name FROM tbl_members WHERE id = '$sender_user_id' LIMIT 1");
    $senderName = ($senderNameQ && $sRow = $senderNameQ->fetch_assoc()) ? $sRow['name'] : "Someone";

    // Send to receiver
    $postDataReceiver = array(
        'receiverId' => $receiver_user_id,
        'type' => 'new_notification',
        'payload' => [
            'type' => 'chat',
            'sender_profile_id' => $my,
            'sender_name' => $senderName,
            'receiverId' => $receiver,
            'platform' => $platform,
            'message' => $chatObj
        ]
    );
    
    // Send to sender's other devices
    $postDataSender = array(
        'room' => "user_{$sender_user_id}",
        'type' => 'new_notification',
        'payload' => [
            'type' => 'chat',
            'sender_profile_id' => $my,
            'sender_name' => $senderName,
            'receiverId' => $receiver,
            'platform' => $platform,
            'message' => $chatObj
        ]
    );

    $options1 = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($postDataReceiver)
        )
    );
    $responseReceiver = @file_get_contents($url, false, stream_context_create($options1));
    $is_online = false;
    if ($responseReceiver) {
        $resObj = json_decode($responseReceiver, true);
        if (isset($resObj['success']) && $resObj['success'] === true) {
            $is_online = true;
        }
    }
    
    // Offline fallback push is now handled reliably by notification_worker.php
    $options2 = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($postDataSender)
        )
    );
    @file_get_contents($url, false, stream_context_create($options2));

    // 5. Push Notification Job
    $redis->lPush(QueueManager::QUEUE_NOTIFICATIONS, json_encode([
        'type' => 'chat',
        'sender_id' => $my,
        'receiver_id' => $receiver,
        'message' => $msg,
        'platform' => $platform
    ]));

    echo json_encode(["status" => "success", "message" => "Sent", "client_message_id" => $client_message_id]);
    exit;
} else {
    // MySQL Fallback
    $stmt = $con->prepare("INSERT IGNORE INTO tbl_messages (client_message_id, sender_id, receiver_id, message, file, file_type, chat_platform, seen, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
    $stmt->bind_param("siissss", $client_message_id, $my, $receiver, $msg, $attachment, $file_type, $platform);
    
    if ($stmt->execute()) {
        $redis = RedisConfig::getConnection();
        if($redis) {
            $redis->lPush(QueueManager::QUEUE_NOTIFICATIONS, json_encode([
                'type' => 'chat',
                'sender_id' => $my,
                'receiver_id' => $receiver,
                'message' => $msg,
                'platform' => $platform
            ]));
        }
        
        $inserted_id = $con->insert_id;
        echo json_encode(["status" => "success", "message" => "Sent", "id" => $inserted_id, "client_message_id" => $client_message_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database execute failed: " . $stmt->error]);
    }
}
?>
