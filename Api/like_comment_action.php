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
    echo json_encode(["status" => "error", "message" => "Auth failed", "debug_user" => $user_id]);
    exit;
}

// Get action from JSON or REQUEST
$action = $data['action'] ?? $_REQUEST['action'] ?? '';

/* ============================
   ❤️ LIKE TOGGLE
============================ */
if ($action === 'like') {
    $pid = intval($data['id'] ?? $_POST['id'] ?? 0);
    if ($pid <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid post id"]);
        exit;
    }
    
    $check = $con->query("SELECT id FROM tbl_likes WHERE post_id=$pid AND user_id=$user_id");

    if ($check->num_rows == 0) {
        $stmt = $con->prepare("INSERT INTO tbl_likes (post_id, user_id, date) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $pid, $user_id);
        if ($stmt->execute()) {
            echo json_encode(["ok" => true, "status" => "liked"]);
        } else {
            echo json_encode(["ok" => false, "message" => "Db error"]);
        }
    } else {
        $con->query("DELETE FROM tbl_likes WHERE post_id=$pid AND user_id=$user_id");
        echo json_encode(["ok" => true, "status" => "unliked"]);
    }
    exit;
}

/* ============================
   💬 COMMENT INSERT
============================ */
if ($action === 'comment') {
    $pid = intval($data['id'] ?? $_POST['id'] ?? 0);
    $comment = trim($data['comment'] ?? $_POST['comment'] ?? '');
    
    if ($pid > 0 && $comment != "") {
        $stmt = $con->prepare("INSERT INTO tbl_comments (post_id, user_id, comment, date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $pid, $user_id, $comment);
        if ($stmt->execute()) {
            echo json_encode(["ok" => true]);
        } else {
            echo json_encode(["ok" => false, "message" => "Db error"]);
        }
    } else {
        echo json_encode(["ok" => false, "message" => "Invalid input"]);
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

        // ✅ Likes count + check if current user liked
        $likes = $con->query("SELECT COUNT(*) FROM tbl_likes WHERE post_id=$pid")->fetch_row()[0];
        $user_liked = $con->query("SELECT id FROM tbl_likes WHERE post_id=$pid AND user_id=$user_id")->num_rows > 0;

        // ✅ Fetch comments
        $comments = [];
        $cres = $con->query("
          SELECT c.comment, c.date, m.name, m.profile_photo
          FROM tbl_comments c 
          JOIN tbl_members m ON c.user_id=m.id 
          WHERE c.post_id=$pid 
          ORDER BY c.date DESC
        ");

        while ($c = $cres->fetch_assoc()) {
            $comments[] = [
                'name' => htmlspecialchars($c['name']),
                'profile_photo' => htmlspecialchars($c['profile_photo']), 
                'comment' => htmlspecialchars($c['comment']),
                'date' => date("d M Y, h:i A", strtotime($c['date']))
            ];
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
