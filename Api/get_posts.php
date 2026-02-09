<?php
include 'headers.php';
include 'connection.php';

// Handle POST Actions (Like / Comment)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? 0;
    $post_id = $_POST['id'] ?? 0; // feedService sends 'id'

    if (!$user_id || !$post_id) {
        echo json_encode(["status" => "error", "message" => "Invalid parameters"]);
        exit;
    }

    if ($action === 'like') {
        $check = $con->query("SELECT id FROM tbl_likes WHERE post_id='$post_id' AND user_id='$user_id'");
        if ($check->num_rows == 0) {
            $con->query("INSERT INTO tbl_likes (post_id, user_id, date) VALUES ('$post_id', '$user_id', NOW())");
        } else {
            $con->query("DELETE FROM tbl_likes WHERE post_id='$post_id' AND user_id='$user_id'");
        }
        echo json_encode(["status" => "success", "message" => "Like toggled"]);
        exit;
    }

    if ($action === 'comment') {
        $comment = trim($_POST['comment'] ?? ''); // feedService doesn't send comment yet in likePost, but if needed
        // Note: feedService.ts doesn't seem to have commentPost yet?
        // Wait, feedService doesn't have comment logic shown in Step 953.
        // But app might use it elsewhere?
        // I'll add it just in case.
        if ($comment) {
            $stmt = $con->prepare("INSERT INTO tbl_comments (post_id, user_id, comment, date) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $post_id, $user_id, $comment);
            $stmt->execute();
            echo json_encode(["status" => "success", "message" => "Comment added"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Comment empty"]);
        }
        exit;
    }
}

$viewer_id = $_GET['user_id'] ?? 0; // Viewer (for liked status)
$filter_user_id = $_GET['filter_user_id'] ?? 0; // Target Profile

$where = " WHERE 1 ";
if ($filter_user_id) {
    $where .= " AND p.user_id = '$filter_user_id' ";
}

$query = "
    SELECT p.*, m.name, m.profile_photo
    FROM tbl_posts p
    JOIN tbl_members m ON p.user_id = m.id
    $where
    ORDER BY p.created_at DESC
";

$res = $con->query($query);
$posts = [];

while ($p = $res->fetch_assoc()) {
    $pid = $p['id'];

    // Likes
    $likes = $con->query("SELECT COUNT(*) FROM tbl_likes WHERE post_id=$pid")->fetch_row()[0];
    $user_liked = false;
    if ($viewer_id) {
        $user_liked = $con->query("SELECT id FROM tbl_likes WHERE post_id=$pid AND user_id=$viewer_id")->num_rows > 0;
    }

    // Comments
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
            'name' => $c['name'],
            'profile_photo' => $c['profile_photo'],
            'comment' => $c['comment'],
            'date' => date("d M Y, h:i A", strtotime($c['date']))
        ];
    }

    // Media
    $media = [];
    if (!empty($p['media'])) {
        $media = array_values(array_filter(explode(',', $p['media'])));
    }

    $posts[] = [
        'id' => $pid,
        'user_id' => $p['user_id'],
        'name' => $p['name'],
        'profile_photo' => $p['profile_photo'],
        'status' => $p['status'],
        'link' => $p['link'],
        'likes' => $likes,
        'user_liked' => $user_liked,
        'comments' => $comments,
        'media' => $media,
        'created_at' => date("d M Y, h:i A", strtotime($p['created_at']))
    ];
}

echo json_encode(["status" => "success", "data" => $posts]);
?>
