<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include("../php/connection.php");

$profile_id = $_POST['profile_id'] ?? 0;
$receiver_id = $_POST['receiver_id'] ?? 0;
$is_typing = $_POST['is_typing'] ?? 0;
$platform = $_POST['platform'] ?? 'marriage';

if(!$profile_id || !$receiver_id) {
    echo json_encode(['status'=>'error']);
    exit;
}

// Update table to include chat_platform if it doesn't exist
$con->query("ALTER TABLE tbl_chat_typing ADD COLUMN IF NOT EXISTS chat_platform VARCHAR(20) DEFAULT 'marriage'");

// Check if row exists (filtered by platform)
$check = $con->query("SELECT id FROM tbl_chat_typing WHERE profile_id='$profile_id' AND target_id='$receiver_id' AND chat_platform='$platform'");
if($check->num_rows > 0){
    $con->query("UPDATE tbl_chat_typing SET is_typing='$is_typing' WHERE profile_id='$profile_id' AND target_id='$receiver_id' AND chat_platform='$platform'");
} else {
    $con->query("INSERT INTO tbl_chat_typing (profile_id, target_id, is_typing, chat_platform) VALUES ('$profile_id', '$receiver_id', '$is_typing', '$platform')");
}

echo json_encode(['status'=>'success']);
?>
