<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'headers.php';
include 'connection.php';

if (!$con) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// Session / Auth Logic
session_start();
header('Content-Type: application/json');
date_default_timezone_set("Asia/Kolkata");

// 1. Get User ID from Request or Session
$user_id = 0;
if (isset($_REQUEST['user_id']) && intval($_REQUEST['user_id']) > 0) {
    $user_id = intval($_REQUEST['user_id']);
}
else if (isset($_SESSION['sadhu_user_id'])) {
    $user_email = $_SESSION['sadhu_user_id'];
    $user = $con->query("SELECT id FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
    if ($user)
        $user_id = $user['id'];
}

// 2. Handle Actions
$action = $_REQUEST['action'] ?? '';

// ACTION: LIKE
if ($action === 'like') {
    if ($user_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Auth failed"]);
        exit;
    }
    $pid = intval($_POST['id']);
    $check = $con->query("SELECT id FROM tbl_likes WHERE post_id=$pid AND user_id=$user_id");

    if ($check->num_rows == 0) {
        $stmt = $con->prepare("INSERT INTO tbl_likes (post_id, user_id, date) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $pid, $user_id);
        $stmt->execute();
    }
    else {
        $con->query("DELETE FROM tbl_likes WHERE post_id=$pid AND user_id=$user_id");
    }
    echo json_encode(["ok" => true]);
    exit;
}

// ACTION: COMMENT
if ($action === 'comment') {
    if ($user_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Auth failed"]);
        exit;
    }
    $pid = intval($_POST['id']);
    $comment = trim($_POST['comment']);
    if ($comment != "") {
        $stmt = $con->prepare("INSERT INTO tbl_comments (post_id, user_id, comment, date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $pid, $user_id, $comment);
        $stmt->execute();
    }
    echo json_encode(["ok" => true]);
    exit;
}

// ACTION: FETCH COMMENTS
if ($action === 'fetch_comments') {
    $pid = intval($_REQUEST['id']);
    $comments = [];

    $use_redis = false;
    require_once 'RedisConfig.php';
    $redis = RedisConfig::getConnection();
    if ($redis) {
        $use_redis = true;
    }

    if ($use_redis) {
        $redisKey = "post:{$pid}:comments";
        $cacheLoadedKey = "post:{$pid}:comments_loaded";

        if (!$redis->exists($cacheLoadedKey)) {
            $cres = $con->query("SELECT c.id, c.user_id, c.comment, c.date, m.name, m.profile_photo FROM tbl_comments c JOIN tbl_members m ON c.user_id=m.id WHERE c.post_id=$pid ORDER BY c.date DESC");
            if ($cres) {
                $cacheComments = [];
                while ($c = $cres->fetch_assoc()) {
                    $cacheComments[] = json_encode([
                        'comment_id' => $c['id'],
                        'user_id' => $c['user_id'],
                        'name' => $c['name'],
                        'profile_photo' => $c['profile_photo'],
                        'comment' => htmlspecialchars($c['comment']),
                        'date' => date("d M Y, h:i A", strtotime($c['date']))
                    ]);
                }
                if (!empty($cacheComments)) {
                    call_user_func_array([$redis, 'rPush'], array_merge([$redisKey], $cacheComments));
                }
                $redis->setex($cacheLoadedKey, 3600, "1");
            }
        }
        $commentsRaw = $redis->lRange($redisKey, 0, -1);
        if ($commentsRaw) {
            foreach ($commentsRaw as $cr) {
                $cObj = json_decode($cr, true);
                if ($cObj) $comments[] = $cObj;
            }
        }
    } else {
        $cres = $con->query("
            SELECT c.id, c.user_id, c.comment, c.date, m.name, m.profile_photo
            FROM tbl_comments c 
            JOIN tbl_members m ON c.user_id=m.id 
            WHERE c.post_id=$pid 
            ORDER BY c.date DESC
        ");
        while ($c = $cres->fetch_assoc()) {
            $comments[] = [
                'comment_id' => $c['id'],
                'user_id' => $c['user_id'],
                'name' => htmlspecialchars($c['name']),
                'profile_photo' => htmlspecialchars($c['profile_photo']),
                'comment' => htmlspecialchars($c['comment']),
                'date' => date("d M Y, h:i A", strtotime($c['date']))
            ];
        }
    }

    // Filter out any comments that are pending deletion (batch not yet processed by worker)
    if ($use_redis && $redis) {
        $pendingDeleteKey = "pending_deletes:comments";
        $pendingRaw = $redis->lRange($pendingDeleteKey, 0, -1);
        if (!empty($pendingRaw)) {
            $pendingIds = [];
            $pendingTexts = []; // To catch temp_ comments by text
            
            foreach ($pendingRaw as $pr) {
                $pObj = json_decode($pr, true);
                if ($pObj) {
                    if (isset($pObj['comment_id'])) {
                        $pendingIds[(string)$pObj['comment_id']] = true;
                    }
                    if (isset($pObj['user_id']) && isset($pObj['comment_text'])) {
                        $hash = $pObj['user_id'] . '_' . md5(htmlspecialchars($pObj['comment_text']));
                        $pendingTexts[$hash] = true;
                    }
                }
            }
            
            if (!empty($pendingIds) || !empty($pendingTexts)) {
                $comments = array_values(array_filter($comments, function($c) use ($pendingIds, $pendingTexts) {
                    $idMatch = isset($pendingIds[(string)$c['comment_id']]);
                    $hash = $c['user_id'] . '_' . md5($c['comment']);
                    $textMatch = isset($pendingTexts[$hash]);
                    return !$idMatch && !$textMatch;
                }));
            }
        }
    }

    echo json_encode($comments);
    exit;
}

// ACTION: DEFAULT (FETCH POSTS)
// Use $user_id already resolved above


// 3. Filter & Pagination Logic
$con->query("CREATE TABLE IF NOT EXISTS tbl_saved_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_post (user_id, post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$filter_user_id = 0;
if (isset($_REQUEST['filter_user_id']) && intval($_REQUEST['filter_user_id']) > 0) {
    $filter_user_id = intval($_REQUEST['filter_user_id']);
}

// PAGINATION PARAMETERS
$limit = intval($_REQUEST['limit'] ?? 20);
$offset = intval($_REQUEST['offset'] ?? 0);
$seed = intval($_REQUEST['seed'] ?? 0);
$cursor_score = isset($_REQUEST['cursor_score']) ? $_REQUEST['cursor_score'] : '0';
$cursor_id = intval($_REQUEST['cursor_id'] ?? 0);

$conditions = [];
$conditions[] = "m.status != 'Blocked'";

if (isset($_REQUEST['filter_saved']) && $_REQUEST['filter_saved'] == '1' && $user_id > 0) {
    $conditions[] = "p.id IN (SELECT post_id FROM tbl_saved_posts WHERE user_id='$user_id')";
} else if ($filter_user_id > 0) {
    $conditions[] = "p.user_id = '$filter_user_id'";
}

if (isset($_REQUEST['filter_reels']) && $_REQUEST['filter_reels'] == '1') {
    $conditions[] = "(p.media LIKE '%.mp4%' OR p.media LIKE '%.mov%' OR p.media LIKE '%.m4v%' OR p.media LIKE '%.3gp%' OR p.media LIKE '%.mkv%')";
}

if (isset($_REQUEST['post_id']) && intval($_REQUEST['post_id']) > 0) {
    $pid_filter = intval($_REQUEST['post_id']);
    $conditions[] = "p.id = '$pid_filter'";
}

$order_clause = "ORDER BY p.id DESC";
if (isset($_REQUEST['filter_reels']) && $_REQUEST['filter_reels'] == '1') {
    $order_clause = "ORDER BY RAND()"; // Make reels random by default
} else if ($filter_user_id == 0 && !isset($_REQUEST['filter_saved']) && !isset($_REQUEST['post_id'])) {
    // Make home feed random too
    if ($seed > 0) {
        $conditions[] = "p.id > (SELECT GREATEST(CAST(IFNULL(MAX(id), 0) AS SIGNED) - 5000, 0) FROM tbl_posts)";
        if (is_numeric($cursor_score) && $cursor_score > 0 && $cursor_id > 0) {
            $conditions[] = "(CRC32(CONCAT(p.id, $seed)) < $cursor_score OR (CRC32(CONCAT(p.id, $seed)) = $cursor_score AND p.id < $cursor_id))";
        }
        $order_clause = "ORDER BY CRC32(CONCAT(p.id, $seed)) DESC, p.id DESC";
    } else {
        $order_clause = "ORDER BY RAND()";
    }
}

$finalWhere = "WHERE " . implode(" AND ", $conditions);

if (isset($_REQUEST['start_post_id']) && intval($_REQUEST['start_post_id']) > 0) {
    $start_post_id = intval($_REQUEST['start_post_id']);
    if (isset($_REQUEST['filter_reels']) && $_REQUEST['filter_reels'] == '1') {
        $order_clause = "ORDER BY (p.id = $start_post_id) DESC, RAND()";
    } else {
        $order_clause = "ORDER BY (p.id = $start_post_id) DESC, p.id DESC";
    }
}

$select_fields = "p.*, m.name, m.profile_photo";
if ($seed > 0 && $filter_user_id == 0 && !isset($_REQUEST['filter_saved']) && !isset($_REQUEST['post_id'])) {
    $select_fields .= ", CRC32(CONCAT(p.id, $seed)) as rand_score";
}

$query = "SELECT $select_fields 
          FROM tbl_posts p
          JOIN tbl_members m ON p.user_id = m.id 
          $finalWhere
          $order_clause
          LIMIT $limit OFFSET $offset";
          
$result = $con->query($query);
$raw_posts = [];
$post_ids = [];

while ($p = $result->fetch_assoc()) {
    $raw_posts[] = $p;
    $post_ids[] = intval($p['id']);
}

if (empty($post_ids)) {
    echo json_encode(["status" => "success", "data" => [], "seed" => $seed]);
    exit;
}

$ids_str = implode(',', $post_ids);

// --- Batch: Likes count per post ---
$likes_map = [];
$lc_res = $con->query("SELECT post_id, COUNT(*) as cnt FROM tbl_likes WHERE post_id IN ($ids_str) GROUP BY post_id");
if ($lc_res) {
    while ($row = $lc_res->fetch_assoc()) {
        $likes_map[$row['post_id']] = intval($row['cnt']);
    }
}

// --- Batch: User liked? ---
$user_liked_map = [];
if ($user_id > 0) {
    $ul_res = $con->query("SELECT post_id FROM tbl_likes WHERE post_id IN ($ids_str) AND user_id=$user_id");
    if ($ul_res) {
        while ($row = $ul_res->fetch_assoc()) {
            $user_liked_map[$row['post_id']] = true;
        }
    }
}

// --- Batch: User saved? ---
$user_saved_map = [];
if ($user_id > 0) {
    $us_res = $con->query("SELECT post_id FROM tbl_saved_posts WHERE post_id IN ($ids_str) AND user_id=$user_id");
    if ($us_res) {
        while ($row = $us_res->fetch_assoc()) {
            $user_saved_map[$row['post_id']] = true;
        }
    }
}

// --- Batch: Comments (latest 3 per post) using Redis or DB ---
require_once 'RedisConfig.php';
$redis = RedisConfig::getConnection();
$comments_map = [];

if ($redis) {
    // Check which posts need DB fetch (not cached)
    $pipe = $redis->multi(\Redis::PIPELINE);
    foreach ($post_ids as $pid) {
        $pipe->exists("post:{$pid}:comments_loaded");
    }
    $exists_results = $pipe->exec();

    $needs_db = [];
    foreach ($post_ids as $i => $pid) {
        if (!$exists_results[$i]) {
            $needs_db[] = $pid;
        }
    }

    // Fetch from DB in one query for all uncached posts
    if (!empty($needs_db)) {
        $needs_str = implode(',', $needs_db);
        $cres = $con->query("SELECT c.id, c.post_id, c.user_id, c.comment, c.date, m.name, m.profile_photo 
                              FROM tbl_comments c 
                              JOIN tbl_members m ON c.user_id=m.id 
                              WHERE c.post_id IN ($needs_str) 
                              ORDER BY c.post_id, c.date DESC");
        $db_comments = [];
        if ($cres) {
            while ($c = $cres->fetch_assoc()) {
                $db_comments[$c['post_id']][] = $c;
            }
        }
        // Store each in Redis
        $pipe2 = $redis->multi(\Redis::PIPELINE);
        foreach ($needs_db as $pid) {
            $redisKey = "post:{$pid}:comments";
            $cacheLoadedKey = "post:{$pid}:comments_loaded";
            $items = $db_comments[$pid] ?? [];
            foreach ($items as $c) {
                $pipe2->rPush($redisKey, json_encode([
                    'comment_id'    => $c['id'],
                    'user_id'       => $c['user_id'],
                    'name'          => $c['name'],
                    'profile_photo' => $c['profile_photo'],
                    'comment'       => htmlspecialchars($c['comment']),
                    'date'          => $c['date']
                ]));
            }
            $pipe2->setex($cacheLoadedKey, 3600, "1");
        }
        $pipe2->exec();
    }

    // Now fetch comments from Redis for all posts
    $pipe3 = $redis->multi(\Redis::PIPELINE);
    foreach ($post_ids as $pid) {
        $pipe3->lRange("post:{$pid}:comments", 0, 2); // latest 3
    }
    $all_comments = $pipe3->exec();
    foreach ($post_ids as $i => $pid) {
        $comments_map[$pid] = [];
        foreach ($all_comments[$i] as $cr) {
            $obj = json_decode($cr, true);
            if ($obj) $comments_map[$pid][] = $obj;
        }
    }
} else {
    // No Redis — single batch query
    $cres = $con->query("SELECT c.id, c.post_id, c.user_id, c.comment, c.date, m.name, m.profile_photo 
                          FROM tbl_comments c 
                          JOIN tbl_members m ON c.user_id=m.id 
                          WHERE c.post_id IN ($ids_str) 
                          ORDER BY c.post_id, c.date DESC");
    if ($cres) {
        while ($c = $cres->fetch_assoc()) {
            $pid = $c['post_id'];
            if (!isset($comments_map[$pid])) $comments_map[$pid] = [];
            if (count($comments_map[$pid]) < 3) {
                $comments_map[$pid][] = [
                    'comment_id'    => $c['id'],
                    'user_id'       => $c['user_id'],
                    'name'          => $c['name'],
                    'profile_photo' => $c['profile_photo'],
                    'comment'       => $c['comment'],
                    'date'          => $c['date']
                ];
            }
        }
    }
}

// --- Build output ---
$posts = [];
foreach ($raw_posts as $p) {
    $pid = intval($p['id']);

    $media = [];
    if (!empty($p['media'])) {
        $media = array_values(array_filter(explode(',', $p['media'])));
    }
    if (empty($media) && !empty($p['image'])) {
        $media[] = $p['image'];
    }

    $image_width = null;
    $image_height = null;
    if (count($media) > 0) {
        $image_path = '../uploads/posts/' . $media[0];
        if (file_exists($image_path)) {
            $dims = @getimagesize($image_path);
            if ($dims) {
                $image_width = $dims[0];
                $image_height = $dims[1];
            }
        }
    }

    $post_item = [
        'id'            => $pid,
        'user_id'       => $p['user_id'],
        'name'          => $p['name'] ?? 'Unknown User',
        'profile_photo' => $p['profile_photo'],
        'description'   => $p['status'] ?? $p['description'] ?? '',
        'link'          => $p['link'] ?? '',
        'likes'         => $likes_map[$pid] ?? 0,
        'user_liked'    => isset($user_liked_map[$pid]),
        'user_saved'    => isset($user_saved_map[$pid]),
        'is_saved'      => isset($user_saved_map[$pid]),
        'comments'      => $comments_map[$pid] ?? [],
        'media'         => $media,
        'image_width'   => $image_width,
        'image_height'  => $image_height,
        'date'          => $p['created_at'] ?? $p['date']
    ];
    if (isset($p['rand_score'])) {
        $post_item['rand_score'] = $p['rand_score'];
    }
    $posts[] = $post_item;
}

echo json_encode(["status" => "success", "data" => $posts, "seed" => $seed]);
?>
