<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'headers.php';
include 'connection.php';

if (!$con) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

$user_id = $_POST['user_id'] ?? '';
$status = $_POST['description'] ?? ''; // App sends 'description', we map to 'status'
$link = $_POST['link'] ?? '';

if(!$user_id){
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

// Upload folder
$uploadDir = "../uploads/posts/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$mediaFiles = [];

// Handle multiple files (media[])
if (isset($_FILES['media']) && is_array($_FILES['media']['name'])) {
    foreach ($_FILES['media']['name'] as $key => $filename) {
        if ($_FILES['media']['error'][$key] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['media']['tmp_name'][$key];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $newName = uniqid('post_') . '.' . $ext;
            $targetFile = $uploadDir . $newName;

            if (move_uploaded_file($tmpName, $targetFile)) {
                $mediaFiles[] = $newName;
            }
        }
    }
}
// Handle single file legacy fallback (if app sends 'image' instead of 'media[]')
else if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['image']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $newName = uniqid('post_') . '.' . $ext;
    $targetFile = $uploadDir . $newName;
    if (move_uploaded_file($tmpName, $targetFile)) {
        $mediaFiles[] = $newName;
    }
}

// Validation: Must have either status text or media
if(empty($status) && empty($mediaFiles)){
    echo json_encode(["status" => "error", "message" => "Content required"]);
    exit;
}

// Prepare Data
$mediaString = !empty($mediaFiles) ? implode(",", $mediaFiles) : NULL;

date_default_timezone_set("Asia/Kolkata");
$date = date("Y-m-d H:i:s");

// Insert into tbl_posts
$stmt = $con->prepare("INSERT INTO tbl_posts (user_id, status, media, link, created_at) VALUES (?, ?, ?, ?, ?)");

if ($stmt) {
    $stmt->bind_param("issss", $user_id, $status, $mediaString, $link, $date);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Post created successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Prepare failed: " . $con->error]);
}
?>
