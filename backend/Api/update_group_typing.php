<?php
// update_group_typing.php
include 'headers.php';
include 'connection.php';

$group_id  = intval($_POST['group_id'] ?? 0);
$user_id   = intval($_POST['user_id'] ?? 0);
$is_typing = intval($_POST['is_typing'] ?? 0);

if (!$group_id || !$user_id) {
    echo json_encode(["status" => "error", "message" => "group_id and user_id required"]);
    exit;
}

// Ensure table exists
$con->query("
    CREATE TABLE IF NOT EXISTS tbl_group_typing (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id INT NOT NULL,
        user_id INT NOT NULL,
        is_typing TINYINT(1) DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_group_user (group_id, user_id)
    )
");

$sql = "
    INSERT INTO tbl_group_typing (group_id, user_id, is_typing, updated_at)
    VALUES ($group_id, $user_id, $is_typing, NOW())
    ON DUPLICATE KEY UPDATE is_typing=$is_typing, updated_at=NOW()
";

if ($con->query($sql)) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $con->error]);
}
?>
