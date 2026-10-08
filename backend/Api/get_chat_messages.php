<?php
include 'headers.php';
include 'connection.php';

$my = intval($_GET['my_profile_id'] ?? 0);
$receiver = intval($_GET['receiver_id'] ?? 0);
$platform = $_GET['platform'] ?? 'community';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 30;
$before_time = isset($_GET['before_time']) ? $con->real_escape_string($_GET['before_time']) : '';
$before_id = isset($_GET['before_id']) ? intval($_GET['before_id']) : 0;
$since_time = isset($_GET['since_time']) ? $con->real_escape_string($_GET['since_time']) : '';
$since_id = isset($_GET['since_id']) ? intval($_GET['since_id']) : 0;

if(!$my || !$receiver){
    echo json_encode(["status" => "error", "message" => "Invalid ID"]);
    exit;
}

// mark seen (filtered by platform)
$updated = $con->query("
    UPDATE tbl_messages
    SET seen=1, seen_at=NOW()
    WHERE receiver_id={$my} AND sender_id={$receiver} AND seen=0 AND chat_platform='{$platform}'
");

// Update Redis
require_once 'RedisConfig.php';
$redis = RedisConfig::getConnection();
if ($redis) {
    $thread_id = min($my, $receiver) . "_" . max($my, $receiver);
    $redisKey = "chat_thread:{$platform}:{$thread_id}";
    $redisMessages = $redis->zRange($redisKey, 0, -1, true);
    foreach ($redisMessages as $rm => $score) {
        $msgObj = json_decode($rm, true);
        if ($msgObj && $msgObj['receiver_id'] == $my && $msgObj['seen'] == 0) {
            $redis->zRem($redisKey, $rm);
            $msgObj['seen'] = 1;
            $redis->zAdd($redisKey, $score, json_encode($msgObj));
        }
    }
}

// If any messages were marked seen, notify the sender via WebSocket
// so their single tick → double tick updates instantly (no need to wait for a reply)
if ($con->affected_rows > 0) {
    $socketPayload = json_encode([
        'receiverId' => $receiver,   // notify the original sender
        'type'       => 'messages_seen',
        'payload'    => [
            'viewer_id'   => $my,
            'receiver_id' => $receiver,
            'sender_id'   => $my,
            'platform'    => $platform
        ]
    ]);
    $ch = curl_init('http://127.0.0.1:3000/api/trigger');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $socketPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Fire-and-forget, don't wait long
    curl_exec($ch);
    curl_close($ch);
}

// fetch messages (filtered by platform)
$cursor_condition = "";
if ($before_time && $before_id) {
    $cursor_condition = " AND (created_at < '{$before_time}' OR (created_at = '{$before_time}' AND id < {$before_id})) ";
} elseif ($before_time) {
    $cursor_condition = " AND created_at < '{$before_time}' ";
} elseif ($since_time && $since_id) {
    $cursor_condition = " AND (created_at > '{$since_time}' OR (created_at = '{$since_time}' AND id > {$since_id})) ";
} elseif ($since_time) {
    $cursor_condition = " AND created_at > '{$since_time}' ";
}

$sql = "
    SELECT id, client_message_id, sender_id, receiver_id, message, file, file_type, is_deleted, seen, created_at
    FROM tbl_messages
    WHERE ((sender_id={$my} AND receiver_id={$receiver})
       OR (sender_id={$receiver} AND receiver_id={$my}))
       AND chat_platform='{$platform}'
       {$cursor_condition}
    ORDER BY created_at DESC, id DESC
    LIMIT {$limit}
";
$res = $con->query($sql);

$messages = [];
$seenMsgIds = [];

while($r = $res->fetch_assoc()){
    $client_id = $r['client_message_id'] ?? $r['id'];
    $seenMsgIds[$client_id] = true;
    
    $messages[] = [
        "id" => $r['id'],
        "client_message_id" => $client_id,
        "message" => $r['message'],
        "sender_id" => $r['sender_id'],
        "is_mine" => ($r['sender_id'] == $my),
        "file" => $r['file'],
        "file_type" => $r['file_type'],
        "is_deleted" => intval($r['is_deleted'] ?? 0),
        "seen" => $r['seen'],
        "created_at" => $r['created_at']
    ];
}

// 2. FETCH PENDING FROM REDIS
require_once 'RedisConfig.php';
$redis = RedisConfig::getConnection();
if ($redis && empty($before_time)) {
    $thread_id = min($my, $receiver) . "_" . max($my, $receiver);
    $redisKey = "chat_thread:{$platform}:{$thread_id}";
    $redisMessages = $redis->zRange($redisKey, 0, -1);
    
    foreach ($redisMessages as $rm) {
        $msgObj = json_decode($rm, true);
        if (!$msgObj) continue;
        
        $cid = $msgObj['client_message_id'] ?? null;
        if ($cid && !isset($seenMsgIds[$cid])) {
            $seenMsgIds[$cid] = true; // Mark as added
            $messages[] = [
                "id" => "pending_" . $cid,
                "client_message_id" => $cid,
                "message" => $msgObj['message'],
                "sender_id" => (int)$msgObj['sender_id'],
                "is_mine" => ((int)$msgObj['sender_id'] == $my),
                "file" => $msgObj['file'],
                "file_type" => $msgObj['file_type'],
                "is_deleted" => (int)$msgObj['is_deleted'],
                "seen" => (int)$msgObj['seen'],
                "created_at" => $msgObj['created_at']
            ];
        }
    }
}

// Sort combined messages by created_at
usort($messages, function($a, $b) {
    return strtotime($a['created_at']) - strtotime($b['created_at']);
});

echo json_encode(["status" => "success", "data" => $messages]);
?>


