<?php
include 'headers.php';
include 'connection.php';
// Auto Migration: Add music_id if missing
$check_col = $con->query("SHOW COLUMNS FROM tbl_stories LIKE 'music_id'");
if ($check_col->num_rows == 0) {
    $con->query("ALTER TABLE tbl_stories ADD COLUMN music_id INT DEFAULT NULL");
}

$user_id = $_POST['user_id'] ?? 0;
$file = $_FILES['story'] ?? null;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

if (!$file || empty($file['name'])) {
    error_log("story_upload.php: No file uploaded for user_id $user_id");
    echo json_encode(["status" => "error", "message" => "No file uploaded"]);
    exit;
}
error_log("story_upload.php: Received file: " . $file['name'] . " size: " . $file['size'] . " type: " . $file['type']);

$uploadDir = "../uploads/stories/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
// If extension is missing, try to guess from type
if (!$ext) {
    if (strpos($file['type'], 'video') !== false) {
        $ext = 'mp4';
    } else {
        $ext = 'jpg';
    }
}

$filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
$targetFile = $uploadDir . $filename;

// Determine type based on MIME type
$type = (strpos($file['type'], 'video') !== false) ? 'video' : 'image';

if (move_uploaded_file($file['tmp_name'], $targetFile)) {
    // Path relative to root, similar to other uploads
    $dbPath = "uploads/stories/" . $filename;
    
    date_default_timezone_set("Asia/Kolkata");
    $date = date("Y-m-d H:i:s");

    $music_id = $_POST['music_id'] ?? null;
    $stmt = $con->prepare("INSERT INTO tbl_stories (user_id, media, type, date, music_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $user_id, $dbPath, $type, $date, $music_id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Story uploaded successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error"]);
    }
} else {
    error_log("story_upload.php: Failed to move uploaded file to $targetFile. Tmp path: " . $file['tmp_name']);
    echo json_encode(["status" => "error", "message" => "Failed to move uploaded file"]);
}
?>
