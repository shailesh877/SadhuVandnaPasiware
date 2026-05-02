<?php
include 'headers.php';
include 'connection.php';

// Handle JSON Input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$profile_id = intval($_REQUEST['profile_id'] ?? $data['profile_id'] ?? 0);
$receiver_id = intval($_REQUEST['receiver_id'] ?? $data['receiver_id'] ?? 0);
$is_typing = intval($_REQUEST['is_typing'] ?? $data['is_typing'] ?? 0);
$platform = $_REQUEST['platform'] ?? $data['platform'] ?? 'marriage';

if(!$profile_id || !$receiver_id){
    echo json_encode(["status" => "error", "message" => "IDs required"]);
    exit;
}

try {
    // Ensure table exists
    $con->query("
        CREATE TABLE IF NOT EXISTS tbl_chat_typing (
            id INT AUTO_INCREMENT PRIMARY KEY,
            profile_id VARCHAR(50) NOT NULL,
            receiver_id VARCHAR(50) NOT NULL,
            is_typing TINYINT(1) DEFAULT 0,
            chat_platform VARCHAR(20) DEFAULT 'marriage',
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_chat_typing (profile_id, receiver_id, chat_platform)
        )
    ");

    // Update or Insert typing status
    // Using a simple table for typing status tracking
    $con->query("
        INSERT INTO tbl_chat_typing (profile_id, receiver_id, is_typing, chat_platform, last_updated)
        VALUES ('$profile_id', '$receiver_id', '$is_typing', '$platform', NOW())
        ON DUPLICATE KEY UPDATE is_typing='$is_typing', last_updated=NOW()
    ");

    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    echo json_encode(["status" => "success"]); // Non-critical, just return success
}
?>
