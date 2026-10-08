<?php
require_once 'headers.php';
require_once 'connection.php';
header('Content-Type: application/json');

$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing user_id']);
    exit;
}

// Get unread notifications count (status = 0 usually means unread, or is_read = 0)
// Assuming tbl_notifications has is_read or status column.
// If there isn't an is_read, maybe we just return 0.
$notifCount = 0;
$nQ = $con->query("SELECT COUNT(*) as cnt FROM tbl_notifications WHERE user_id = '$user_id' AND seen = 0");
if ($nQ) {
    $notifCount = $nQ->fetch_assoc()['cnt'];
}

// Get unread chat count
$chatCount = 0;
// Typically unread chats is from tbl_messages where receiver_id = user_id and seen = 0
$cQ = $con->query("SELECT COUNT(*) as cnt FROM tbl_messages WHERE receiver_id = '$user_id' AND seen = 0 AND is_deleted = 0");
if ($cQ) {
    $chatCount = $cQ->fetch_assoc()['cnt'];
}

echo json_encode([
    'status' => 'success',
    'notifications' => (int)$notifCount,
    'chats' => (int)$chatCount
]);
?>
