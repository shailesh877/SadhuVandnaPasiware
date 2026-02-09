<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include("../php/connection.php");

$profile_id = $_POST['profile_id'] ?? 0;
$receiver_id = $_POST['receiver_id'] ?? 0;
$is_typing = $_POST['is_typing'] ?? 0;

if(!$profile_id || !$receiver_id) {
    echo json_encode(['status'=>'error']);
    exit;
}

// Create table if not exists
$con->query("CREATE TABLE IF NOT EXISTS tbl_chat_typing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT,
    target_id INT,
    is_typing TINYINT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Check if row exists
$check = $con->query("SELECT id FROM tbl_chat_typing WHERE profile_id='$profile_id' AND target_id='$receiver_id'");
if($check->num_rows > 0){
    $con->query("UPDATE tbl_chat_typing SET is_typing='$is_typing' WHERE profile_id='$profile_id' AND target_id='$receiver_id'");
} else {
    $con->query("INSERT INTO tbl_chat_typing (profile_id, target_id, is_typing) VALUES ('$profile_id', '$receiver_id', '$is_typing')");
}

echo json_encode(['status'=>'success']);
?>
