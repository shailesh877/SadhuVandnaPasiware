<?php
header('Content-Type: application/json');
include("../connection.php"); // path adjust if needed

$my_id     = intval($_POST['my_id'] ?? 0);
$target_id = intval($_POST['target_id'] ?? 0);
$action    = $_POST['action'] ?? ''; // block | unblock | check
$platform  = $_POST['platform'] ?? 'marriage';

if (!$my_id || !$target_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid user data"
    ]);
    exit;
}

// Ensure chat_platform column exists in tbl_blocked_users
$con->query("ALTER TABLE tbl_blocked_users ADD COLUMN IF NOT EXISTS chat_platform VARCHAR(20) DEFAULT 'marriage'");

if ($action === 'block') {

    $stmt = $con->prepare(
        "INSERT IGNORE INTO tbl_blocked_users (blocker_id, blocked_id, chat_platform) VALUES (?, ?, ?)"
    );
    $stmt->bind_param("iis", $my_id, $target_id, $platform);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "action" => "blocked"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to block user"
        ]);
    }

} elseif ($action === 'unblock') {

    $stmt = $con->prepare(
        "DELETE FROM tbl_blocked_users WHERE blocker_id=? AND blocked_id=? AND chat_platform=?"
    );
    $stmt->bind_param("iis", $my_id, $target_id, $platform);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "action" => "unblocked"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to unblock user"
        ]);
    }

} elseif ($action === 'check') {
    
    $stmt = $con->prepare(
        "SELECT id FROM tbl_blocked_users WHERE blocker_id=? AND blocked_id=? AND chat_platform=?"
    );
    $stmt->bind_param("iis", $my_id, $target_id, $platform);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(["status" => "success", "blocked" => true]);
    } else {
        echo json_encode(["status" => "success", "blocked" => false]);
    }

} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid action"
    ]);
}
