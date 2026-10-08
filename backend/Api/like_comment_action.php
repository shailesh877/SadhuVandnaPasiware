<?php
// Api/like_comment_action.php
include("headers.php"); // Added CORS headers for App
include("connection.php");
session_start();
header('Content-Type: application/json');
date_default_timezone_set("Asia/Kolkata");

// Handle JSON Input from React Native App
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$user_id = 0;
// Read from JSON payload, or POST/GET, or Session
if (isset($data['user_id']) && intval($data['user_id']) > 0) {
    $user_id = intval($data['user_id']);
} else if (isset($_REQUEST['user_id']) && intval($_REQUEST['user_id']) > 0) {
    $user_id = intval($_REQUEST['user_id']);
} else if (isset($_SESSION['sadhu_user_id'])) {
    $user_email = $_SESSION['sadhu_user_id'];
    $user = $con->query("SELECT id FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
    if ($user) $user_id = $user['id'];
}

if ($user_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Auth failed"]);
    exit;
}

// Get action from JSON or REQUEST
$action = $data['action'] ?? $_REQUEST['action'] ?? '';

// WebSocket helper
function triggerWebSocket($room, $type, $payload, $isGlobal = true) {
    $url = 'http://127.0.0.1:3000/api/trigger';
    $postData = array(
        'room' => $room,
        'type' => $type,
        'payload' => $payload,
        'global' => $isGlobal
    );
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($postData),
            'timeout' => 1
        )
    );
    $context  = stream_context_create($options);
    @file_get_contents($url, false, $context);
}

/* ============================
   ❤️ LIKE TOGGLE (REDIS + QUEUE)
============================ */
if ($action === 'like') {
    $pid = intval($data['id'] ?? $_POST['id'] ?? 0);
    if ($pid <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid post id"]);
        exit;
    }
    
    require_once 'RedisConfig.php';
    require_once 'QueueManager.php';
    
    $redis = RedisConfig::getConnection();
    if (!$redis) {
        // Fallback to MySQL directly if Redis is down
        $check = $con->query("SELECT id FROM tbl_likes WHERE post_id=$pid AND user_id=$user_id");
        if ($check->num_rows == 0) {
            $con->query("INSERT INTO tbl_likes (post_id, user_id, date) VALUES ($pid, $user_id, NOW())");
            echo json_encode(["ok" => true, "status" => "liked"]);
        } else {
            $con->query("DELETE FROM tbl_likes WHERE post_id=$pid AND user_id=$user_id");
            echo json_encode(["ok" => true, "status" => "unliked"]);
        }
        exit;
    }
    
    $redisKey = "post:{$pid}:likes";
    $cacheLoadedKey = "post:{$pid}:likes_loaded";
    
    // 1. Warm up cache if not loaded
    if (!$redis->exists($cacheLoadedKey)) {
        $likesQ = $con->query("SELECT user_id FROM tbl_likes WHERE post_id=$pid");
        if ($likesQ && $likesQ->num_rows > 0) {
            $userIds = [];
            while ($row = $likesQ->fetch_assoc()) {
                $userIds[] = $row['user_id'];
            }
            if (!empty($userIds)) {
                // Check if sAddArray method exists, otherwise use multiple sAdd or call_user_func_array
                call_user_func_array([$redis, 'sAdd'], array_merge([$redisKey], $userIds));
            }
        }
        $redis->set($cacheLoadedKey, 1, ['ex' => 86400]);
        $redis->expire($redisKey, 86400); 
    }
    
    // 2. Toggle in Redis (extremely fast)
    $isLiked = $redis->sIsMember($redisKey, $user_id);
    if ($isLiked) {
        $redis->sRem($redisKey, $user_id);
        $status = "unliked";
    } else {
        $redis->sAdd($redisKey, $user_id);
        $status = "liked";
    }
    
    // 3. Queue for async persistence (Worker will clear lock when done)
    QueueManager::pushJob('sync_like_state', [
        'post_id' => $pid,
        'user_id' => $user_id
    ], "sync_like_{$pid}_{$user_id}", 600);
    
    // 4. WebSocket Broadcast
    triggerWebSocket("post_{$pid}", "post_like_updated", ["post_id" => $pid, "user_id" => $user_id, "status" => $status]);

    echo json_encode(["ok" => true, "status" => $status]);
    exit;
}

/* ============================
   🔖 SAVE / BOOKMARK TOGGLE
============================ */
if ($action === 'save' || $action === 'toggle_save') {
    $pid = intval($data['id'] ?? $data['post_id'] ?? $_POST['id'] ?? $_POST['post_id'] ?? $_REQUEST['id'] ?? $_REQUEST['post_id'] ?? 0);
    if ($pid <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid post id"]);
        exit;
    }

    $con->query("CREATE TABLE IF NOT EXISTS tbl_saved_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_post (user_id, post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $check = $con->query("SELECT id FROM tbl_saved_posts WHERE post_id=$pid AND user_id=$user_id");

    if ($check && $check->num_rows == 0) {
        $stmt = $con->prepare("INSERT INTO tbl_saved_posts (post_id, user_id, date) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $pid, $user_id);
        if ($stmt->execute()) {
            echo json_encode(["ok" => true, "status" => "saved", "is_saved" => true]);
        } else {
            echo json_encode(["ok" => false, "message" => "Db error: " . $con->error]);
        }
    } else {
        $con->query("DELETE FROM tbl_saved_posts WHERE post_id=$pid AND user_id=$user_id");
        echo json_encode(["ok" => true, "status" => "unsaved", "is_saved" => false]);
    }
    exit;
}

/* ============================
   💬 COMMENT INSERT (MySQL first → real ID → Redis)
============================ */
if ($action === 'comment') {
    $pid     = intval($data['id'] ?? $_POST['id'] ?? 0);
    $comment = trim($data['comment'] ?? $_POST['comment'] ?? '');

    if ($pid > 0 && $comment != "") {
        require_once 'RedisConfig.php';
        $redis = RedisConfig::getConnection();

        // 1. Insert directly into MySQL → get real auto-increment ID
        $stmt = $con->prepare("INSERT INTO tbl_comments (post_id, user_id, comment, date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $pid, $user_id, $comment);
        if (!$stmt->execute()) {
            echo json_encode(["ok" => false, "message" => "DB error: " . $con->error]);
            exit;
        }
        $real_id = $con->insert_id; // ← real DB id, e.g. 233

        // 2. Fetch user info for Redis entry
        $userQ = $con->query("SELECT name, profile_photo FROM tbl_members WHERE id=$user_id LIMIT 1");
        $name = '';
        $profile_photo = '';
        if ($userQ && $userRow = $userQ->fetch_assoc()) {
            $name          = $userRow['name'];
            $profile_photo = $userRow['profile_photo'];
        }

        $date       = date("Y-m-d H:i:s");
        $commentObj = [
            'comment_id'    => $real_id,          // ← real ID, not temp_
            'user_id'       => $user_id,
            'name'          => htmlspecialchars($name),
            'profile_photo' => htmlspecialchars($profile_photo),
            'comment'       => htmlspecialchars($comment),
            'date'          => date("d M Y, h:i A", strtotime($date))
        ];

        // 3. Push to Redis with real ID (wipe stale cache first)
        if ($redis) {
            $cacheLoadedKey = "post:{$pid}:comments_loaded";
            $redisKey       = "post:{$pid}:comments";

            // Ensure cache is warm before pushing
            if (!$redis->exists($cacheLoadedKey)) {
                $cres = $con->query("SELECT c.id, c.user_id, c.comment, c.date, m.name, m.profile_photo FROM tbl_comments c JOIN tbl_members m ON c.user_id=m.id WHERE c.post_id=$pid ORDER BY c.date DESC");
                if ($cres) {
                    $cacheComments = [];
                    while ($c = $cres->fetch_assoc()) {
                        $cacheComments[] = json_encode([
                            'comment_id'    => $c['id'],
                            'user_id'       => $c['user_id'],
                            'name'          => htmlspecialchars($c['name']),
                            'profile_photo' => htmlspecialchars($c['profile_photo']),
                            'comment'       => htmlspecialchars($c['comment']),
                            'date'          => date("d M Y, h:i A", strtotime($c['date']))
                        ]);
                    }
                    if (!empty($cacheComments)) {
                        call_user_func_array([$redis, 'rPush'], array_merge([$redisKey], $cacheComments));
                    }
                }
                $redis->set($cacheLoadedKey, 1, ['ex' => 3600]);
                $redis->expire($redisKey, 3600);
            } else {
                // Cache already loaded — just prepend the new comment
                $redis->lPush($redisKey, json_encode($commentObj));
            }
        }

        // 4. Broadcast via WebSocket
        triggerWebSocket("post_{$pid}", "post_comment_added", ["post_id" => $pid, "comment" => $commentObj]);

        // 5. Return real ID to frontend
        echo json_encode(["ok" => true, "status" => "commented", "comment_id" => $real_id]);
    } else {
        echo json_encode(["ok" => false, "message" => "Invalid input"]);
    }
    exit;
}


/* ============================
   🗑️ COMMENT DELETE
============================ */
if ($action === 'delete_comment') {
    $pid        = intval($data['post_id'] ?? $_POST['post_id'] ?? 0);
    $comment_id = trim($data['comment_id'] ?? $_POST['comment_id'] ?? '');
    $comment_text = trim($data['comment'] ?? $_POST['comment'] ?? '');

    if ($pid > 0 && $comment_id !== '') {
        require_once 'RedisConfig.php';
        $redis = RedisConfig::getConnection();

        $redisKey       = "post:{$pid}:comments";
        $cacheLoadedKey = "post:{$pid}:comments_loaded";

        // ── Step 1: Wipe Redis cache completely ─────────────────────────────
        if ($redis) {
            $redis->del($cacheLoadedKey);
            $redis->del($redisKey);
        }

        // ── Step 2: Delete from MySQL ────────────────────────────────────────
        $affected = 0;

        if (strpos($comment_id, 'temp_') === 0) {
            // temp_ comment — worker may have already stored it with real ID.
            // Try by text match (both raw and html-decoded variants)
            $decoded = htmlspecialchars_decode($comment_text);
            $encoded = htmlspecialchars($comment_text);

            // Try 1: exact text as sent
            $stmt = $con->prepare("DELETE FROM tbl_comments WHERE post_id=? AND user_id=? AND comment=? LIMIT 1");
            $stmt->bind_param("iis", $pid, $user_id, $comment_text);
            $stmt->execute();
            $affected = $stmt->affected_rows;

            // Try 2: html-decoded version
            if ($affected === 0 && $decoded !== $comment_text) {
                $stmt2 = $con->prepare("DELETE FROM tbl_comments WHERE post_id=? AND user_id=? AND comment=? LIMIT 1");
                $stmt2->bind_param("iis", $pid, $user_id, $decoded);
                $stmt2->execute();
                $affected = $stmt2->affected_rows;
            }

            // Try 3: html-encoded version
            if ($affected === 0 && $encoded !== $comment_text) {
                $stmt3 = $con->prepare("DELETE FROM tbl_comments WHERE post_id=? AND user_id=? AND comment=? LIMIT 1");
                $stmt3->bind_param("iis", $pid, $user_id, $encoded);
                $stmt3->execute();
                $affected = $stmt3->affected_rows;
            }
        } else {
            // Real DB ID — straightforward delete
            $stmt = $con->prepare("DELETE FROM tbl_comments WHERE id=? AND user_id=?");
            $stmt->bind_param("ii", $comment_id, $user_id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
        }

        triggerWebSocket("post_{$pid}", "post_comment_deleted", ["post_id" => $pid, "comment_id" => $comment_id]);
        echo json_encode(["ok" => true, "status" => "deleted", "affected" => $affected]);
    } else {
        echo json_encode(["ok" => false, "message" => "Invalid input"]);
    }
    exit;
}



/* ============================
   ✏️ COMMENT EDIT (REDIS + QUEUE)
============================ */
if ($action === 'edit_comment') {
    $pid = intval($data['post_id'] ?? $_POST['post_id'] ?? 0);
    $comment_id = $data['comment_id'] ?? $_POST['comment_id'] ?? '';
    $new_comment = trim($data['comment'] ?? $_POST['comment'] ?? '');

    if ($pid > 0 && $comment_id !== '' && $new_comment !== '') {
        require_once 'RedisConfig.php';
        require_once 'QueueManager.php';
        $redis = RedisConfig::getConnection();
        
        if ($redis) {
            $redisKey = "post:{$pid}:comments";
            $cacheLoadedKey = "post:{$pid}:comments_loaded";
            
            $authorized = false;
            if ($redis->exists($cacheLoadedKey)) {
                $len = $redis->lLen($redisKey);
                for ($i=0; $i<$len; $i++) {
                    $cr = $redis->lIndex($redisKey, $i);
                    $cObj = json_decode($cr, true);
                    if ($cObj && isset($cObj['comment_id']) && (string)$cObj['comment_id'] === (string)$comment_id) {
                        // Check ownership
                        if (isset($cObj['user_id']) && (int)$cObj['user_id'] === (int)$user_id) {
                            $authorized = true;
                            // Immediately update the comment in Redis by replacing the JSON
                            $cObj['comment'] = htmlspecialchars($new_comment);
                            $redis->lSet($redisKey, $i, json_encode($cObj));
                        } else {
                            echo json_encode(["ok" => false, "message" => "Unauthorized edit"]);
                            exit;
                        }
                        break;
                    }
                }
            }
            
            if ($authorized) {
                QueueManager::pushJob('sync_edit_comment', [
                    'post_id' => $pid,
                    'user_id' => $user_id,
                    'comment_id' => $comment_id,
                    'comment' => $new_comment
                ], "sync_edit_comment_{$comment_id}", 600);
                
                triggerWebSocket("post_{$pid}", "post_comment_updated", ["post_id" => $pid, "comment_id" => $comment_id, "comment" => $new_comment]);
                echo json_encode(["ok" => true, "status" => "edited"]);
            } else {
                echo json_encode(["ok" => false, "message" => "Comment not found or unauthorized"]);
            }
        } else {
            // DB Fallback
            $stmt = $con->prepare("UPDATE tbl_comments SET comment=? WHERE id=? AND user_id=?");
            $stmt->bind_param("sii", $new_comment, $comment_id, $user_id);
            $stmt->execute();
            triggerWebSocket("post_{$pid}", "post_comment_updated", ["post_id" => $pid, "comment_id" => $comment_id, "comment" => $new_comment]);
            echo json_encode(["ok" => true, "status" => "edited"]);
        }
    } else {
        echo json_encode(["ok" => false, "message" => "Invalid input"]);
    }
    exit;
}

/* ============================
   📢 REPORT POST
============================ */
if ($action === 'report') {
    $pid = intval($data['id'] ?? $_POST['id'] ?? 0);
    $reason = trim($data['reason'] ?? $_POST['reason'] ?? '');

    if ($pid > 0 && $reason != "") {
        $stmt = $con->prepare("INSERT INTO tbl_reports (post_id, user_id, reason, date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $pid, $user_id, $reason);
        if ($stmt->execute()) {
            echo json_encode(["ok" => true]);
        } else {
            echo json_encode(["ok" => false, "message" => "Db error: " . $con->error]);
        }
    } else {
        echo json_encode(["ok" => false, "message" => "Invalid input: pid=$pid, reason=$reason"]);
    }
    exit;
}

/* ============================
   📦 FETCH ALL POSTS (with likes/comments)
============================ */
if ($action === 'fetch_all') {
    $posts = [];

    // ✅ Filter by specific user (if user_id passed)
    $where = "";
    if (isset($_GET['user_id']) && intval($_GET['user_id']) > 0) {
        $uid = intval($_GET['user_id']);
        $where = "WHERE p.user_id = $uid";
    }

    $res = $con->query("
      SELECT p.*, m.name, m.profile_photo
      FROM tbl_posts p
      JOIN tbl_members m ON p.user_id = m.id
      $where
      ORDER BY p.created_at DESC
    ");

    while ($p = $res->fetch_assoc()) {
        $pid = $p['id'];

        // ✅ Likes count + check if current user liked via Redis
        $likes = 0;
        $user_liked = false;
        
        require_once 'RedisConfig.php';
        $redis = RedisConfig::getConnection();
        
        if ($redis) {
            $redisKey = "post:{$pid}:likes";
            $cacheLoadedKey = "post:{$pid}:likes_loaded";
            
            if (!$redis->exists($cacheLoadedKey)) {
                $likesQ = $con->query("SELECT user_id FROM tbl_likes WHERE post_id=$pid");
                if ($likesQ && $likesQ->num_rows > 0) {
                    $userIds = [];
                    while ($row = $likesQ->fetch_assoc()) $userIds[] = $row['user_id'];
                    if (!empty($userIds)) {
                        call_user_func_array([$redis, 'sAdd'], array_merge([$redisKey], $userIds));
                    }
                }
                $redis->set($cacheLoadedKey, 1, ['ex' => 86400]);
                $redis->expire($redisKey, 86400);
            }
            $likes = $redis->sCard($redisKey);
            $user_liked = $redis->sIsMember($redisKey, $user_id);
        } else {
            // Fallback
            $likes = $con->query("SELECT COUNT(*) FROM tbl_likes WHERE post_id=$pid")->fetch_row()[0];
            $user_liked = $con->query("SELECT id FROM tbl_likes WHERE post_id=$pid AND user_id=$user_id")->num_rows > 0;
        }

        // ✅ Fetch comments via Redis
        $comments = [];
        if (isset($redis) && $redis) {
            $redisKey = "post:{$pid}:comments";
            $cacheLoadedKey = "post:{$pid}:comments_loaded";
            
            if (!$redis->exists($cacheLoadedKey)) {
                $cres = $con->query("SELECT c.id, c.user_id, c.comment, c.date, m.name, m.profile_photo FROM tbl_comments c JOIN tbl_members m ON c.user_id=m.id WHERE c.post_id=$pid ORDER BY c.date DESC");
                if ($cres) {
                    $cacheComments = [];
                    while ($c = $cres->fetch_assoc()) {
                        $cacheComments[] = json_encode([
                            'comment_id' => $c['id'],
                            'name' => htmlspecialchars($c['name']),
                            'profile_photo' => htmlspecialchars($c['profile_photo']), 
                            'comment' => htmlspecialchars($c['comment']),
                            'date' => date("d M Y, h:i A", strtotime($c['date']))
                        ]);
                    }
                    if (!empty($cacheComments)) {
                        call_user_func_array([$redis, 'rPush'], array_merge([$redisKey], $cacheComments));
                    }
                }
                $redis->set($cacheLoadedKey, 1, ['ex' => 86400]);
                $redis->expire($redisKey, 86400);
            }
            
            $commentsRaw = $redis->lRange($redisKey, 0, -1);
            if ($commentsRaw) {
                foreach ($commentsRaw as $cr) {
                    $cObj = json_decode($cr, true);
                    if ($cObj) $comments[] = $cObj;
                }
            }
        } else {
            // Fallback to MySQL
            $cres = $con->query("
              SELECT c.id, c.user_id, c.comment, c.date, m.name, m.profile_photo
              FROM tbl_comments c 
              JOIN tbl_members m ON c.user_id=m.id 
              WHERE c.post_id=$pid 
              ORDER BY c.date DESC
            ");
            if ($cres) {
                while ($c = $cres->fetch_assoc()) {
                    $comments[] = [
                        'comment_id' => $c['id'],
                        'name' => htmlspecialchars($c['name']),
                        'profile_photo' => htmlspecialchars($c['profile_photo']), 
                        'comment' => htmlspecialchars($c['comment']),
                        'date' => date("d M Y, h:i A", strtotime($c['date']))
                    ];
                }
            }
        }

        // ✅ Media split fix
        $media = [];
        if (!empty($p['media'])) {
            $media = array_filter(explode(',', $p['media']));
        }

        $posts[] = [
            'id' => $pid,
            'user_id' => $p['user_id'],
            'name' => htmlspecialchars($p['name']),
            'profile_photo' => htmlspecialchars($p['profile_photo']),
            'status' => htmlspecialchars($p['status']),
            'link' => htmlspecialchars($p['link']),
            'likes' => $likes,
            'user_liked' => $user_liked,
            'comments' => $comments,
            'media' => array_values($media),
            'date' => date("d M Y, h:i A", strtotime($p['created_at']))
        ];
    }

    echo json_encode($posts);
    exit;
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
?>
