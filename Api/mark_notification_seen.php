<?php
include("connection.php");

$user_id = $_POST['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

// 1. Mark system notifications as seen
$con->query("UPDATE tbl_notifications SET seen = 1 WHERE user_id = '$user_id'");

echo json_encode(["status" => "success"]);
?>
