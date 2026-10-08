<?php
// Api/notification_worker.php
require_once __DIR__ . '/QueueManager.php';
require_once __DIR__ . '/connection.php'; 
require_once __DIR__ . '/push_helper.php';

echo "Starting background notification worker...\n";
$redis = RedisConfig::getConnection();

if (!$redis) die("Redis not available.\n");

function processGroupedNotification($con, $redis, $receiver_id, $type, $reference_id, $actor_id, $actor_name, $default_title, $payload_data) {
    if ($type === 'follow_request' || $type === 'follow' || $type === 'message') {
        if ($type === 'follow') {
            $msg = $actor_name . ' accepted your request and followed you back!';
        } elseif ($type === 'follow_request') {
            $msg = $actor_name . ' wants to connect with you.';
        } else {
            $msg = $actor_name . ' sent you a message.';
            if (isset($payload_data['is_group']) && $payload_data['is_group']) {
                $group_id = $payload_data['group_id'];
                $default_title = $payload_data['group_name'] ?? "New Group Message";
                $msg = $actor_name . ': ' . ($payload_data['message_text'] ?? "Sent an attachment");
                $payload_data['collapseId'] = "group_{$group_id}";
            } else {
                $default_title = $actor_name;
                $payload_data['collapseId'] = "chat_{$actor_id}";
                
                $platform = $payload_data['platform'] ?? 'community';
                $db_receiver_id = $payload_data['receiver_profile_id'] ?? $receiver_id;
                $unreadQ = $con->query("SELECT message FROM tbl_messages WHERE sender_id = '$actor_id' AND receiver_id = '$db_receiver_id' AND seen = 0 AND chat_platform = '$platform' ORDER BY id ASC LIMIT 5");
                
                $unreadMsgs = [];
                if ($unreadQ && $unreadQ->num_rows > 0) {
                    while($uRow = $unreadQ->fetch_assoc()) {
                        $unreadMsgs[] = !empty($uRow['message']) ? $uRow['message'] : "Sent an attachment";
                    }
                    $msg = implode("\n", $unreadMsgs);
                } else {
                    $msg = $payload_data['message_text'] ?? "Sent an attachment";
                    if (empty(trim($msg))) $msg = "Sent an attachment";
                }
            }
        }
        deliverNotification($con, $receiver_id, $type, $reference_id, $default_title, $msg, $payload_data);
        return;
    }

    $redisKey = "notify:{$type}:{$reference_id}:actors";
    $redis->sAdd($redisKey, $actor_name);
    $redis->expire($redisKey, 7 * 24 * 3600); 

    $actors = $redis->sMembers($redisKey);
    $count = count($actors);
    
    $actors = array_diff($actors, [$actor_name]);
    array_unshift($actors, $actor_name);
    $actors = array_values($actors);
    
    if ($count == 1) {
        $msg = "{$actor_name} " . ($type == 'like' ? 'liked' : 'commented on') . " your post.";
    } elseif ($count == 2) {
        $msg = "{$actors[0]} and {$actors[1]} " . ($type == 'like' ? 'liked' : 'commented on') . " your post.";
    } else {
        $others = $count - 2;
        $msg = "{$actors[0]}, {$actors[1]} and {$others} other" . ($others > 1 ? 's' : '') . " " . ($type == 'like' ? 'liked' : 'commented on') . " your post.";
    }
    
    deliverNotification($con, $receiver_id, $type, $reference_id, $default_title, $msg, $payload_data);
}

function deliverNotification($con, $receiver_id, $type, $reference_id, $title, $msg, $payload_data) {
    $payload_json = json_encode($payload_data);
    $notif_id = 0;
    
    // Group only unread notifications (seen = 0)
    $checkQ = $con->query("SELECT id FROM tbl_notifications WHERE user_id = '$receiver_id' AND type = '$type' AND reference_id = '$reference_id' AND seen = 0 LIMIT 1");
    if ($checkQ && $row = $checkQ->fetch_assoc()) {
        $notif_id = $row['id'];
        $stmt = $con->prepare("UPDATE tbl_notifications SET message = ?, created_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $msg, $notif_id);
        $stmt->execute();
    } else {
        $stmt = $con->prepare("INSERT INTO tbl_notifications (user_id, title, message, type, data_payload, reference_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $receiver_id, $title, $msg, $type, $payload_json, $reference_id);
        $stmt->execute();
        $notif_id = $con->insert_id;
    }
    
    // Attempt WebSocket Delivery
    $ch = curl_init('http://127.0.0.1:3000/api/trigger');
    $postPayload = json_encode([
        'receiverId' => $receiver_id,
        'type' => 'bell_notification',
        'payload' => [
            'id' => $notif_id,
            'title' => $title,
            'message' => $msg,
            'type' => $type,
            'data_payload' => $payload_json,
            'created_at' => date('Y-m-d H:i:s'),
            'seen' => 0
        ]
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $success = false;
    if ($httpCode == 200 && $response) {
        $resObj = json_decode($response, true);
        if (isset($resObj['success']) && $resObj['success'] === true) {
            $success = true;
        }
    }
    
    // Always send FCM Push because WebSocket success doesn't mean the user is in foreground.
    // The frontend NotificationHandler suppresses it in foreground if needed.
    sendExpoPushNotification($con, $receiver_id, $title, $msg, $payload_data, true);
}

while (true) {
    try {
        $json = $redis->brPop(QueueManager::QUEUE_NOTIFICATIONS, 5);
        if (!$json || !isset($json[1])) continue;
        
        $payload = json_decode($json[1], true);
        if (!$payload) continue;
        
        $type = $payload['type'];
        if ($type === 'like') {
            $pid = $payload['post_id'];
            $uid = $payload['user_id'];
            $ownQ = $con->query("SELECT p.user_id, m.name as liker_name FROM tbl_posts p, tbl_members m WHERE p.id = $pid AND m.id = $uid LIMIT 1");
            if ($ownQ && $row = $ownQ->fetch_assoc()) {
                $owner_id = $row['user_id'];
                $liker_name = $row['liker_name'];
                if ($owner_id != $uid) {
                    processGroupedNotification($con, $redis, $owner_id, 'like', "post_{$pid}", $uid, $liker_name, "New Like", ["type" => "like", "postId" => strval($pid)]);
                }
            }
        } elseif ($type === 'comment') {
            $pid = $payload['post_id'];
            $uid = $payload['user_id'];
            $ownQ = $con->query("SELECT p.user_id, m.name as commenter_name FROM tbl_posts p, tbl_members m WHERE p.id = $pid AND m.id = $uid LIMIT 1");
            if ($ownQ && $row = $ownQ->fetch_assoc()) {
                $owner_id = $row['user_id'];
                $commenter_name = $row['commenter_name'];
                if ($owner_id != $uid) {
                    processGroupedNotification($con, $redis, $owner_id, 'comment', "post_{$pid}", $uid, $commenter_name, "New Comment", ["type" => "comment", "postId" => strval($pid)]);
                }
            }
        } elseif ($type === 'follow') {
            $follower_id = $payload['follower_id'];
            $following_id = $payload['following_id'];
            $status = $payload['status'];
            
            $sQ = $con->query("SELECT name FROM tbl_members WHERE id = $follower_id LIMIT 1");
            $sender_name = ($sQ && $sRow = $sQ->fetch_assoc()) ? $sRow['name'] : "Someone";
            
            if ($status === 'accepted') {
                processGroupedNotification($con, $redis, $following_id, 'follow', "user_{$follower_id}", $follower_id, $sender_name, "New Connection", ["type" => "follow", "user_id" => $follower_id]);
            } else {
                processGroupedNotification($con, $redis, $following_id, 'follow_request', "user_{$follower_id}", $follower_id, $sender_name, "Follow Request", ["type" => "follow_request", "user_id" => $follower_id]);
            }
        } elseif ($type === 'chat') {
            $sender_id = $payload['sender_id'];
            $receiver_id = $payload['receiver_id'];
            $message_text = $payload['message'] ?? '';
            $platform = $payload['platform'] ?? 'community';
            
            if ($platform === 'marriage') {
                $sQ = $con->query("SELECT full_name as name, photo, user_id FROM tbl_marriage_profiles WHERE id = $sender_id LIMIT 1");
                $sender_name = "Someone";
                $sender_photo = "";
                $sender_user_id = $sender_id;
                if ($sQ && $sRow = $sQ->fetch_assoc()) {
                    $sender_name = $sRow['name'];
                    $sender_photo = $sRow['photo'];
                    $sender_user_id = $sRow['user_id'];
                }
                
                $rQ = $con->query("SELECT user_id FROM tbl_marriage_profiles WHERE id = $receiver_id LIMIT 1");
                $actual_receiver_id = ($rQ && $rRow = $rQ->fetch_assoc()) ? $rRow['user_id'] : 0;
            } else {
                $sQ = $con->query("SELECT name, profile_photo as photo FROM tbl_members WHERE id = $sender_id LIMIT 1");
                $sender_name = "Someone";
                $sender_photo = "";
                $sender_user_id = $sender_id;
                if ($sQ && $sRow = $sQ->fetch_assoc()) {
                    $sender_name = $sRow['name'];
                    $sender_photo = $sRow['photo'];
                }
                $actual_receiver_id = $receiver_id;
            }
            
            if ($actual_receiver_id) {
                processGroupedNotification($con, $redis, $actual_receiver_id, 'message', "chat_{$sender_id}", $sender_id, $sender_name, "New Message", ["type" => "message", "sender_id" => strval($sender_id), "message_text" => $message_text, "platform" => $platform, "receiver_profile_id" => strval($receiver_id), "sender_name" => $sender_name, "sender_photo" => $sender_photo, "sender_user_id" => strval($sender_user_id)]);
            }
        } elseif ($type === 'group_chat') {
            $sender_id = $payload['sender_id'];
            $group_id = $payload['group_id'];
            $message_text = $payload['message'] ?? '';
            
            // Get all group members except sender
            $gQ = $con->query("SELECT user_id FROM tbl_group_members WHERE group_id = $group_id AND user_id != $sender_id");
            if ($gQ && $gQ->num_rows > 0) {
                $sQ = $con->query("SELECT name FROM tbl_members WHERE id = $sender_id LIMIT 1");
                $sender_name = ($sQ && $sRow = $sQ->fetch_assoc()) ? $sRow['name'] : "Someone";
                
                $grpQ = $con->query("SELECT name FROM tbl_groups WHERE id = $group_id LIMIT 1");
                $group_name = ($grpQ && $grpRow = $grpQ->fetch_assoc()) ? $grpRow['name'] : "Group";
                
                while ($gRow = $gQ->fetch_assoc()) {
                    $receiver_id = $gRow['user_id'];
                    processGroupedNotification($con, $redis, $receiver_id, 'message', "group_{$group_id}", $sender_id, $sender_name, "New Group Message", ["type" => "message", "sender_id" => strval($sender_id), "group_id" => strval($group_id), "is_group" => true, "group_name" => $group_name, "message_text" => $message_text]);
                }
            }
        }
    } catch (Exception $e) {
        echo "Notification worker error: " . $e->getMessage() . "\n";
    }
}
?>




