<?php
header('Content-Type: application/json');
include("../connection.php"); // path adjust if needed

$my_id     = intval($_POST['my_id'] ?? 0);
$target_id = intval($_POST['target_id'] ?? 0);
$action    = $_POST['action'] ?? ''; // block | unblock

if (!$my_id || !$target_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid user data"
    ]);
    exit;
}

if ($action === 'block') {

    $stmt = $con->prepare(
        "INSERT IGNORE INTO tbl_blocked_users (blocker_id, blocked_id) VALUES (?, ?)"
    );
    $stmt->bind_param("ii", $my_id, $target_id);

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
        "DELETE FROM tbl_blocked_users WHERE blocker_id=? AND blocked_id=?"
    );
    $stmt->bind_param("ii", $my_id, $target_id);

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

} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid action"
    ]);
}
