<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    include 'connection.php';

    // Handle JSON Input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $my_id     = intval($_REQUEST['my_id'] ?? $data['my_id'] ?? 0);
    $target_id = intval($_REQUEST['target_id'] ?? $data['target_id'] ?? 0);
    $action    = $_REQUEST['action'] ?? $data['action'] ?? ''; // block | unblock | check
    $platform  = $_REQUEST['platform'] ?? $data['platform'] ?? 'marriage';

    if (!$my_id || !$target_id) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid user data"
        ]);
        exit;
    }

    // Ensure chat_platform column exists in tbl_blocked_users (Compatible way)
    $check_col = $con->query("SHOW COLUMNS FROM tbl_blocked_users LIKE 'chat_platform'");
    if ($check_col && $check_col->num_rows == 0) {
        $con->query("ALTER TABLE tbl_blocked_users ADD COLUMN chat_platform VARCHAR(20) DEFAULT 'marriage'");
    }

    // Robust index management: Check if unique_block already exists
    $idx_check = $con->query("SHOW INDEX FROM tbl_blocked_users WHERE Key_name = 'unique_block'");
    if ($idx_check && $idx_check->num_rows == 0) {
        // Try to drop old ones IF they exist (ignoring errors)
        @$con->query("ALTER TABLE tbl_blocked_users DROP INDEX blocker_id");
        @$con->query("ALTER TABLE tbl_blocked_users DROP INDEX blocked_id");
        // Add new composite index
        $con->query("ALTER TABLE tbl_blocked_users ADD UNIQUE INDEX unique_block (blocker_id, blocked_id, chat_platform)");
    }

    if ($action === 'block') {
        $stmt = $con->prepare("INSERT INTO tbl_blocked_users (blocker_id, blocked_id, chat_platform) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE chat_platform=VALUES(chat_platform)");
        $stmt->bind_param("iis", $my_id, $target_id, $platform);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "action" => "blocked"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to block user: ".$stmt->error]);
        }
    } elseif ($action === 'unblock') {
        $stmt = $con->prepare("DELETE FROM tbl_blocked_users WHERE blocker_id=? AND blocked_id=? AND chat_platform=?");
        $stmt->bind_param("iis", $my_id, $target_id, $platform);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "action" => "unblocked"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to unblock user"]);
        }
    } elseif ($action === 'check') {
        $stmt = $con->prepare("SELECT id FROM tbl_blocked_users WHERE 
            ((blocker_id=? AND blocked_id=?) OR (blocker_id=? AND blocked_id=?)) 
            AND chat_platform=?");
        $stmt->bind_param("iiiis", $my_id, $target_id, $target_id, $my_id, $platform);
        $stmt->execute();
        $stmt->store_result();
        echo json_encode(["status" => "success", "blocked" => ($stmt->num_rows > 0)]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
