<?php
include 'headers.php';
include 'connection.php';

$user_id = $_POST['user_id'] ?? 0;
$status = $_POST['status'] ?? '';
$link = $_POST['link'] ?? '';

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

$uploadDir = "../uploads/posts/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

$mediaFiles = [];

if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
    foreach ($_FILES['media']['name'] as $key => $filename) {
        $tmpName = $_FILES['media']['tmp_name'][$key];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $newName = uniqid('post_') . '.' . $ext;
        $targetFile = $uploadDir . $newName;

        if (move_uploaded_file($tmpName, $targetFile)) {
            $mediaFiles[] = $newName;
        }
    }
}

$mediaString = !empty($mediaFiles) ? implode(",", $mediaFiles) : NULL;
date_default_timezone_set("Asia/Kolkata");
$date = date("Y-m-d H:i:s");

$stmt = $con->prepare("INSERT INTO tbl_posts (user_id, status, media, link, created_at) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $user_id, $status, $mediaString, $link, $date);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Post created", "data" => ["id" => $stmt->insert_id]]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}
?>
