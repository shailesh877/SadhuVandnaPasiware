<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include("connection.php");

$data = json_decode(file_get_contents("php://input"), true);
$message_id = $data['message_id'] ?? $_POST['message_id'] ?? null;
$my_id = $data['my_profile_id'] ?? $_POST['my_profile_id'] ?? null;
$receiver_id = $data['receiver_id'] ?? $_POST['receiver_id'] ?? null;
$platform = $data['platform'] ?? $_POST['platform'] ?? 'marriage';

if ($message_id && $my_id) {
    if (is_numeric($message_id)) {
        $con->query("UPDATE tbl_messages SET seen = 1 WHERE id = '$message_id' OR client_message_id = '$message_id'");
    } else {
        $con->query("UPDATE tbl_messages SET seen = 1 WHERE client_message_id = '$message_id'");
    }
    
    // Update Redis
    require_once 'RedisConfig.php';
    $redis = RedisConfig::getConnection();
    if ($redis && $receiver_id) {
        $thread_id = min($my_id, $receiver_id) . "_" . max($my_id, $receiver_id);
        $redisKey = "chat_thread:{$platform}:{$thread_id}";
        $redisMessages = $redis->zRange($redisKey, 0, -1, true);
        foreach ($redisMessages as $rm => $score) {
            $msgObj = json_decode($rm, true);
            if ($msgObj && ((isset($msgObj['id']) && $msgObj['id'] === $message_id) || (isset($msgObj['client_message_id']) && $msgObj['client_message_id'] === $message_id))) {
                $redis->zRem($redisKey, $rm);
                $msgObj['seen'] = 1;
                $redis->zAdd($redisKey, $score, json_encode($msgObj));
            }
        }
    }

    // Broadcast seen event back to sender
    $url = 'http://127.0.0.1:3000/api/trigger';
    $postData = [
        'room' => "user_{$receiver_id}",
        'type' => 'messages_seen',
        'payload' => [
            'viewer_id' => $my_id,
            'message_id' => $message_id,
            'platform' => $platform
        ]
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    curl_exec($ch);
    curl_close($ch);
    
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid parameters"]);
}
?>



)");
    
    // Update Redis
    require_once 'RedisConfig.php';
    $redis = RedisConfig::getConnection();
    if ($redis && $receiver_id) {
        $thread_id = min($my_id, $receiver_id) . "_" . max($my_id, $receiver_id);
        $redisKey = "chat_thread:{$platform}:{$thread_id}";
        $redisMessages = $redis->zRange($redisKey, 0, -1, true);
        foreach ($redisMessages as $rm => $score) {
            $msgObj = json_decode($rm, true);
            if ($msgObj && ((isset($msgObj['id']) && $msgObj['id'] === $message_id) || (isset($msgObj['client_message_id']) && $msgObj['client_message_id'] === $message_id))) {
                $redis->zRem($redisKey, $rm);
                $msgObj['seen'] = 1;
                $redis->zAdd($redisKey, $score, json_encode($msgObj));
            }
        }
    }

    // Broadcast seen event back to sender
    $url = 'http://127.0.0.1:3000/api/trigger';
    $postData = [
        'room' => "user_{$receiver_id}",
        'type' => 'messages_seen',
        'payload' => [
            'viewer_id' => $my_id,
            'message_id' => $message_id,
            'platform' => $platform
        ]
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    curl_exec($ch);
    curl_close($ch);
    
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid parameters"]);
}
?>







