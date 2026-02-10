<?php
// Api/delete_story.php
require_once("connection.php");

$user_id = intval($_POST['user_id'] ?? 0);
$story_id = intval($_POST['story_id'] ?? 0);

if (!$user_id || !$story_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

// Verify ownership
$check = $con->query("SELECT id, media FROM tbl_stories WHERE id='$story_id' AND user_id='$user_id' LIMIT 1");
if ($row = $check->fetch_assoc()) {
    // Delete file
    if (file_exists("../" . $row['media'])) {
        unlink("../" . $row['media']);
    }
    
    // Delete DB record
    $con->query("DELETE FROM tbl_stories WHERE id='$story_id'");
    $con->query("DELETE FROM tbl_story_views WHERE story_id='$story_id'");
    
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Not found or unauthorized']);
}
?>
