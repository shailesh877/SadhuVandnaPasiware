<?php
include 'headers.php';
include 'connection.php';

// Handle JSON Input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$message_id_raw = $_REQUEST['message_id'] ?? $data['message_id'] ?? null;
$my = intval($_REQUEST['my_profile_id'] ?? $data['my_profile_id'] ?? 0);

if(!$message_id_raw || !$my){ 
    echo json_encode(["status" => "error", "message" => "Invalid ID"]);
    exit; 
}

// Clean up "pending_" prefix if present
if (strpos($message_id_raw, 'pending_') === 0) {
    $message_id_raw = substr($message_id_raw, 8);
}

$is_numeric = is_numeric($message_id_raw);

try {
    $is_group = (isset($_REQUEST['is_group']) && $_REQUEST['is_group'] == '1') || (isset($data['is_group']) && $data['is_group'] == '1');
    
    // First find receiver_id to update Redis & broadcast
    $receiver_id = 0;
    $platform = 'marriage';
    
    if ($is_group) {
        $q = $is_numeric ? "SELECT group_id FROM tbl_group_messages WHERE id='$message_id_raw'" : "SELECT group_id FROM tbl_group_messages WHERE client_message_id='$message_id_raw'";
        $res = $con->query($q);
        if ($res && $r = $res->fetch_assoc()) $receiver_id = $r['group_id'];
    } else {
        $q = $is_numeric ? "SELECT receiver_id, chat_platform FROM tbl_messages WHERE id='$message_id_raw'" : "SELECT receiver_id, chat_platform FROM tbl_messages WHERE client_message_id='$message_id_raw'";
        $res = $con->query($q);
        if ($res && $r = $res->fetch_assoc()) {
            $receiver_id = $r['receiver_id'];
            $platform = $r['chat_platform'];
        }
    }

    if ($is_group) {
        if ($is_numeric) {
            $stmt = $con->prepare("UPDATE tbl_group_messages SET is_deleted = 1, message = '🚫 This message was deleted', attachment = '' WHERE id=? AND sender_id=?");
            $stmt->bind_param("ii", $message_id_raw, $my);
        } else {
            $stmt = $con->prepare("UPDATE tbl_group_messages SET is_deleted = 1, message = '🚫 This message was deleted', attachment = '' WHERE client_message_id=? AND sender_id=?");
            $stmt->bind_param("si", $message_id_raw, $my);
        }
    } else {
        if ($is_numeric) {
            $stmt = $con->prepare("UPDATE tbl_messages SET is_deleted = 1, message = '🚫 This message was deleted', file = '', file_type = NULL WHERE id=? AND sender_id=?");
            $stmt->bind_param("ii", $message_id_raw, $my);
        } else {
            $stmt = $con->prepare("UPDATE tbl_messages SET is_deleted = 1, message = '🚫 This message was deleted', file = '', file_type = NULL WHERE client_message_id=? AND sender_id=?");
            $stmt->bind_param("si", $message_id_raw, $my);
        }
    }
    
    $stmt->execute();

    if($stmt->affected_rows > 0 || !$is_numeric) {
        // Also update Redis if single chat
        if (!$is_group && $receiver_id) {
            require_once 'RedisConfig.php';
            $redis = RedisConfig::getConnection();
            if ($redis) {
                $thread_id = min($my, $receiver_id) . "_" . max($my, $receiver_id);
                $redisKey = "chat_thread:{$platform}:{$thread_id}";
                $redisMessages = $redis->zRange($redisKey, 0, -1, true);
                foreach ($redisMessages as $rm => $score) {
                    $msgObj = json_decode($rm, true);
                    if ($msgObj && ((isset($msgObj['id']) && $msgObj['id'] == $message_id_raw) || (isset($msgObj['client_message_id']) && $msgObj['client_message_id'] == $message_id_raw))) {
                        $redis->zRem($redisKey, $rm);
                        $msgObj['is_deleted'] = 1;
                        $msgObj['message'] = '🚫 This message was deleted';
                        $msgObj['file'] = '';
                        $msgObj['file_type'] = null;
                        $redis->zAdd($redisKey, $score, json_encode($msgObj));
                    }
                }
            }
        }
        
        // Broadcast delete event
        $url = 'http://127.0.0.1:3000/api/trigger';
        $postData = [
            'room' => $is_group ? "group_{$receiver_id}" : "user_{$receiver_id}",
            'type' => 'chat_deleted',
            'payload' => [
                'message_id' => $message_id_raw,
                'is_group' => $is_group
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

        echo json_encode(["status" => "success", "message" => "Deleted"]);
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "Failed or Not Authorized",
            "debug" => [
                "message_id" => $message_id_raw,
                "sender_id" => $my,
                "is_group" => $is_group,
                "affected" => $stmt->affected_rows
            ]
        ]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
