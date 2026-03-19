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

    // Fix Unique constraints - Drop old if exists and add new composite
    // Note: We use a custom flag or just try-catch to avoid errors if already done
    $con->query("ALTER TABLE tbl_blocked_users DROP INDEX blocker_id, DROP INDEX blocked_id"); // Drop individual if they exist
    $con->query("ALTER TABLE tbl_blocked_users DROP INDEX unique_block"); // Drop old composite if exists
    $con->query("ALTER TABLE tbl_blocked_users ADD UNIQUE INDEX unique_block (blocker_id, blocked_id, chat_platform)");

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
