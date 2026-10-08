<?php
// worker.php
require_once __DIR__ . '/QueueManager.php';
require_once __DIR__ . '/connection.php'; 

echo "Starting background batch queue worker...\n";

$redis = RedisConfig::getConnection();
if (!$redis) {
    die("Redis is not available. Please ensure Redis server is running.\n");
}

$maxRetries = 3;
$recovered = QueueManager::recoverOrphanedJobs();
if ($recovered > 0) echo "Recovered {$recovered} orphaned jobs on startup.\n";

$lastRecoveryTime = time();

while (true) {
    if (time() - $lastRecoveryTime > 300) {
        $recovered = QueueManager::recoverOrphanedJobs();
        if ($recovered > 0) echo "Recovered {$recovered} orphaned jobs during housekeeping.\n";
        $lastRecoveryTime = time();
    }

    try {
        $jobs = QueueManager::popBatch(200); // Batch of up to 200 jobs
        
        if (empty($jobs)) {
            sleep(1); // Wait if empty
            continue;
        }

        // 1. Group jobs by type
        $groupedJobs = [];
        foreach ($jobs as $jobItem) {
            $type = $jobItem['data']['type'];
            if (!isset($groupedJobs[$type])) $groupedJobs[$type] = [];
            $groupedJobs[$type][] = $jobItem;
        }

        // 2. Process each group
        foreach ($groupedJobs as $type => $group) {
            try {
                processBatch($type, $group);
                
                // Finalize all if batch success
                foreach ($group as $jobItem) {
                    QueueManager::finalizeJob($jobItem['json']);
                    if (!empty($jobItem['data']['deduplication_key'])) {
                        $redis->del($jobItem['data']['deduplication_key']);
                    }
                }
            } catch (Exception $e) {
                echo "[" . date('Y-m-d H:i:s') . "] Error in batch type {$type}: " . $e->getMessage() . "\n";
                // Requeue or fail on error
                foreach ($group as $jobItem) {
                    $jobData = $jobItem['data'];
                    $jobData['attempts'] = ($jobData['attempts'] ?? 0) + 1;
                    if ($jobData['attempts'] < $maxRetries) {
                        QueueManager::requeueJob($jobData, $jobItem['json']);
                    } else {
                        QueueManager::failJob($jobData, $e->getMessage());
                        QueueManager::finalizeJob($jobItem['json']);
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Fatal worker error: " . $e->getMessage() . "\n";
        sleep(2);
    }
}

function processBatch($type, $jobs) {
    global $con;
    $redis = RedisConfig::getConnection();
    if (!$redis) throw new Exception("Redis not available");

    if ($type === 'sync_like_state') {
        $inserts = [];
        $deletes = [];
        $notifications = [];
        
        // Deduplicate within the batch
        $uniquePayloads = [];
        foreach ($jobs as $jobItem) {
            $p = $jobItem['data']['payload'];
            $key = $p['post_id'] . '_' . $p['user_id'];
            $uniquePayloads[$key] = $p;
        }

        foreach ($uniquePayloads as $payload) {
            $pid = (int)$payload['post_id'];
            $uid = (int)$payload['user_id'];
            
            $redisKey = "post:{$pid}:likes";
            $isLikedInRedis = $redis->sIsMember($redisKey, $uid);
            
            if ($isLikedInRedis) {
                $inserts[] = "($pid, $uid, NOW())";
                $notifications[] = ['type' => 'like', 'post_id' => $pid, 'user_id' => $uid];
            } else {
                $deletes[] = "(post_id=$pid AND user_id=$uid)";
            }
        }
        
        if (!empty($inserts)) {
            $sql = "INSERT IGNORE INTO tbl_likes (post_id, user_id, date) VALUES " . implode(',', $inserts);
            if (!$con->query($sql)) throw new Exception("Bulk Insert Likes Failed: " . $con->error);
        }
        if (!empty($deletes)) {
            $sql = "DELETE FROM tbl_likes WHERE " . implode(' OR ', $deletes);
            if (!$con->query($sql)) throw new Exception("Bulk Delete Likes Failed: " . $con->error);
        }
        
        foreach ($notifications as $n) {
            $redis->lPush(QueueManager::QUEUE_NOTIFICATIONS, json_encode($n));
        }
    } 
    else if ($type === 'sync_follow_state') {
        $inserts = [];
        $deletes = [];
        require_once 'RedisFollowHelper.php';
        
        $uniquePayloads = [];
        foreach ($jobs as $jobItem) {
            $p = $jobItem['data']['payload'];
            $key = $p['follower_id'] . '_' . $p['following_id'];
            $uniquePayloads[$key] = $p;
        }

        foreach ($uniquePayloads as $payload) {
            $follower_id = (int)$payload['follower_id'];
            $following_id = (int)$payload['following_id'];
            
            $finalStatus = RedisFollowHelper::getStatus($redis, $con, $follower_id, $following_id);
            if ($finalStatus) {
                // Ensure safe string
                $fs = $con->real_escape_string($finalStatus);
                $inserts[] = "($follower_id, $following_id, '$fs')";
                $notifications[] = ['type' => 'follow', 'follower_id' => $follower_id, 'following_id' => $following_id, 'status' => $finalStatus];
            } else {
                $deletes[] = "(follower_id=$follower_id AND following_id=$following_id)";
            }
        }
        
        if (!empty($inserts)) {
            $sql = "INSERT INTO tbl_followers (follower_id, following_id, status) VALUES " . implode(',', $inserts) . " ON DUPLICATE KEY UPDATE status=VALUES(status)";
            if (!$con->query($sql)) throw new Exception("Bulk Follow Insert Failed: " . $con->error);
        }
        if (!empty($deletes)) {
            $sql = "DELETE FROM tbl_followers WHERE " . implode(' OR ', $deletes);
            if (!$con->query($sql)) throw new Exception("Bulk Follow Delete Failed: " . $con->error);
        }
        
        if (isset($notifications) && !empty($notifications)) {
            foreach ($notifications as $n) {
                $redis->lPush(QueueManager::QUEUE_NOTIFICATIONS, json_encode($n));
            }
        }
    }
    else if ($type === 'sync_comment' || $type === 'sync_edit_comment' || $type === 'sync_delete_comment') {
        // Use transactions for comments since they vary widely
        $con->begin_transaction();
        
        try {
            foreach ($jobs as $jobItem) {
                $payload = $jobItem['data']['payload'];
                $pid = (int)$payload['post_id'];
                $uid = (int)$payload['user_id'];
                
                if ($type === 'sync_comment') {
                    $comment = $payload['comment'];
                    $date = $payload['date'];
                    $temp_id = $payload['temp_id'] ?? '';
                    
                    $found = false;
                    if ($temp_id) {
                        $redisKey = "post:{$pid}:comments";
                        $commentsRaw = $redis->lRange($redisKey, 0, -1);
                        foreach ($commentsRaw as $cr) {
                            $cObj = json_decode($cr, true);
                            if ($cObj && isset($cObj['comment_id']) && (string)$cObj['comment_id'] === (string)$temp_id) {
                                $found = true;
                                $comment = htmlspecialchars_decode($cObj['comment']);
                                break;
                            }
                        }
                    } else {
                        $found = true;
                    }
                    
                    if ($found) {
                        $stmt = $con->prepare("INSERT INTO tbl_comments (post_id, user_id, comment, date) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("iiss", $pid, $uid, $comment, $date);
                        if ($stmt->execute()) {
                            $n = ['type' => 'comment', 'post_id' => $pid, 'user_id' => $uid];
                            $redis->lPush(QueueManager::QUEUE_NOTIFICATIONS, json_encode($n));
                        }
                    }
                }
                else if ($type === 'sync_edit_comment') {
                    $comment_id = $payload['comment_id'];
                    $new_comment = $payload['comment'];
                    
                    $redisKey = "post:{$pid}:comments";
                    $commentsRaw = $redis->lRange($redisKey, 0, -1);
                    $found = false;
                    foreach ($commentsRaw as $cr) {
                        $cObj = json_decode($cr, true);
                        if ($cObj && isset($cObj['comment_id']) && (string)$cObj['comment_id'] === (string)$comment_id) {
                            $found = true;
                            $new_comment = htmlspecialchars_decode($cObj['comment']);
                            break;
                        }
                    }
                    
                    if ($found && strpos($comment_id, 'temp_') === false) {
                        $stmt = $con->prepare("UPDATE tbl_comments SET comment=? WHERE id=? AND user_id=?");
                        $stmt->bind_param("sii", $new_comment, $comment_id, $uid);
                        $stmt->execute();
                    }
                }
                else if ($type === 'sync_delete_comment') {
                    $comment_id = $payload['comment_id'];
                    $comment_text = htmlspecialchars_decode($payload['comment']);
                    
                    if (strpos($comment_id, 'temp_') === 0) {
                        $stmt = $con->prepare("DELETE FROM tbl_comments WHERE post_id=? AND user_id=? AND comment=? LIMIT 1");
                        $stmt->bind_param("iis", $pid, $uid, $comment_text);
                    } else {
                        $stmt = $con->prepare("DELETE FROM tbl_comments WHERE id=? AND user_id=?");
                        $stmt->bind_param("ii", $comment_id, $uid);
                    }
                    if (isset($stmt)) $stmt->execute();
                }
            }
            $con->commit();
        } catch (Exception $e) {
            $con->rollback();
            throw $e;
        }
    }
    else if ($type === 'batch_delete_comments') {
        $pendingDeleteKey = "pending_deletes:comments";
        // Get all pending deletes from Redis
        $pendingRaw = $redis->lRange($pendingDeleteKey, 0, -1);
        
        if (empty($pendingRaw)) return;
        
        $con->begin_transaction();
        try {
            $realIdsToDelete = [];
            $affectedPostIds = []; // Track post IDs for cache invalidation
            
            foreach ($pendingRaw as $pr) {
                $pObj = json_decode($pr, true);
                if (!$pObj) continue;
                
                $comment_id = $pObj['comment_id'];
                $pid = $pObj['post_id'];
                $uid = $pObj['user_id'];
                $text = $pObj['comment_text'];
                
                $affectedPostIds[$pid] = true; // collect for cache bust
                
                if (strpos($comment_id, 'temp_') === 0) {
                    // Delete temp comments one by one (they don't have DB IDs yet)
                    $stmt = $con->prepare("DELETE FROM tbl_comments WHERE post_id=? AND user_id=? AND comment=? LIMIT 1");
                    $stmt->bind_param("iis", $pid, $uid, $text);
                    $stmt->execute();
                } else {
                    $realIdsToDelete[] = (int)$comment_id;
                }
            }
            
            // Delete all real IDs in one single query
            if (!empty($realIdsToDelete)) {
                $ids_str = implode(',', $realIdsToDelete);
                $con->query("DELETE FROM tbl_comments WHERE id IN ($ids_str)");
            }
            
            $con->commit();
            
            // Clear the pending list from Redis now that DB is updated
            $redis->del($pendingDeleteKey);
            
            // Bust the comments cache for every affected post so next fetch reads fresh data from DB
            foreach (array_keys($affectedPostIds) as $affPid) {
                $redis->del("post:{$affPid}:comments_loaded");
                // Also wipe the stale Redis comments list — worker already deleted from DB,
                // next fetch_comments will rebuild it cleanly from DB.
                $redis->del("post:{$affPid}:comments");
            }
            
        } catch (Exception $e) {
            $con->rollback();
            throw $e;
        }
    }
    else if ($type === 'sync_chat') {
        $inserts = [];
        $uniquePayloads = [];
        
        foreach ($jobs as $jobItem) {
            $p = $jobItem['data']['payload'];
            $key = $p['client_message_id'];
            $uniquePayloads[$key] = $p;
        }

        foreach ($uniquePayloads as $payload) {
            $cid = $con->real_escape_string($payload['client_message_id']);
            $sender = (int)$payload['sender_id'];
            $receiver = (int)$payload['receiver_id'];
            $msg = $con->real_escape_string($payload['message'] ?? '');
            $file = $con->real_escape_string($payload['file'] ?? '');
            $file_type = $con->real_escape_string($payload['file_type'] ?? '');
            $plat = $con->real_escape_string($payload['chat_platform'] ?? '');
            
            $inserts[] = "('$cid', $sender, $receiver, '$msg', '$file', '$file_type', '$plat', 0, NOW())";
        }
        
        if (!empty($inserts)) {
            $sql = "INSERT IGNORE INTO tbl_messages (client_message_id, sender_id, receiver_id, message, file, file_type, chat_platform, seen, created_at) VALUES " . implode(',', $inserts);
            if (!$con->query($sql)) throw new Exception("Bulk Chat Insert Failed: " . $con->error);
        }
    }
    else if ($type === 'mark_seen_batch') {
        $client_ids = [];
        $numeric_ids = [];
        
        foreach ($jobs as $jobItem) {
            $p = $jobItem['data']['payload'];
            $mid = $p['message_id'];
            if (!empty($p['is_numeric'])) {
                $numeric_ids[] = (int)$mid;
            } else {
                $client_ids[] = "'" . $con->real_escape_string($mid) . "'";
            }
        }
        
        if (!empty($numeric_ids) || !empty($client_ids)) {
            $where = [];
            if (!empty($numeric_ids)) {
                $where[] = "id IN (" . implode(',', array_unique($numeric_ids)) . ")";
            }
            if (!empty($client_ids)) {
                $where[] = "client_message_id IN (" . implode(',', array_unique($client_ids)) . ")";
            }
            
            $sql = "UPDATE tbl_messages SET seen = 1 WHERE " . implode(' OR ', $where);
            if (!$con->query($sql)) throw new Exception("Bulk Mark Seen Failed: " . $con->error);
        }
    }
    else if ($type === 'update_last_seen') {
        $user_ids = [];
        
        foreach ($jobs as $jobItem) {
            $uid = (int)$jobItem['data']['payload']['user_id'];
            $user_ids[$uid] = $uid;
        }
        
        if (!empty($user_ids)) {
            $sql = "UPDATE tbl_members SET last_active=NOW() - INTERVAL 5 MINUTE WHERE id IN (" . implode(',', $user_ids) . ")";
            if (!$con->query($sql)) throw new Exception("Bulk Last Seen Update Failed: " . $con->error);
        }
    }
    else if ($type === 'sync_group_chat') {
        $inserts = [];
        $uniquePayloads = [];
        
        foreach ($jobs as $jobItem) {
            $p = $jobItem['data']['payload'];
            $key = $p['client_message_id'];
            $uniquePayloads[$key] = $p;
        }

        foreach ($uniquePayloads as $payload) {
            $cid = $con->real_escape_string($payload['client_message_id']);
            $gid = (int)$payload['group_id'];
            $sender = (int)$payload['sender_id'];
            $msg = $con->real_escape_string($payload['message']);
            $file = $con->real_escape_string($payload['attachment']);
            $file_type = $con->real_escape_string($payload['file_type']);
            
            $inserts[] = "('$cid', $gid, $sender, '$msg', '$file', '$file_type', NOW())";
        }
        
        if (!empty($inserts)) {
            $sql = "INSERT IGNORE INTO tbl_group_messages (client_message_id, group_id, sender_id, message, attachment, file_type, created_at) VALUES " . implode(',', $inserts);
            if (!$con->query($sql)) throw new Exception("Bulk Group Chat Insert Failed: " . $con->error);
        }
    }
}
?>
