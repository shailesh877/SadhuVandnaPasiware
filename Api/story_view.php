<?php
// Api/story_view.php
require_once("connection.php");
date_default_timezone_set('Asia/Kolkata');

$user_id = intval($_POST['user_id'] ?? 0); // Viewer
$story_id = intval($_POST['story_id'] ?? 0);

if (!$user_id || !$story_id) {
    echo json_encode(['status' => 'error']);
    exit;
}

// Check if already viewed
$check = $con->query("SELECT id FROM tbl_story_views WHERE story_id='$story_id' AND viewer_id='$user_id' LIMIT 1");
if ($check->num_rows == 0) {
    $stmt = $con->prepare("INSERT INTO tbl_story_views (story_id, viewer_id, viewed_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $story_id, $user_id);
    $stmt->execute();
}

echo json_encode(['status' => 'success']);
?>
