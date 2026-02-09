<?php
include("connection.php");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Content-Type: application/json");

$channel_id = $_POST['channel_id'] ?? '';

if (!$channel_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing channel_id']);
    exit;
}

// We use caller_peer_id to store the channel name (e.g., call_123_456)
$stmt = $con->prepare("SELECT status FROM tbl_calls WHERE caller_peer_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("s", $channel_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['status' => 'success', 'call_status' => $row['status']]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Call not found']);
}
?>
