<?php
include("headers.php");
include("connection.php");
session_start();
header('Content-Type: application/json');
date_default_timezone_set("Asia/Kolkata");

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$user_id = intval($data['user_id'] ?? $_REQUEST['user_id'] ?? 0);
$post_id = intval($data['post_id'] ?? $data['id'] ?? $_REQUEST['post_id'] ?? $_REQUEST['id'] ?? 0);

if ($user_id <= 0 || $post_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid user or post ID"]);
    exit;
}

$con->query("CREATE TABLE IF NOT EXISTS tbl_saved_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_post (user_id, post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$check = $con->query("SELECT id FROM tbl_saved_posts WHERE post_id=$post_id AND user_id=$user_id");

if ($check && $check->num_rows == 0) {
    $stmt = $con->prepare("INSERT INTO tbl_saved_posts (post_id, user_id, date) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $post_id, $user_id);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "action" => "saved", "is_saved" => true]);
    } else {
        echo json_encode(["status" => "error", "message" => "Db error: " . $con->error]);
    }
} else {
    $con->query("DELETE FROM tbl_saved_posts WHERE post_id=$post_id AND user_id=$user_id");
    echo json_encode(["status" => "success", "action" => "unsaved", "is_saved" => false]);
}
?>
