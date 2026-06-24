<?php
include 'headers.php';
include 'connection.php';

$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';

if (!$title || !$description) {
    echo json_encode(["status" => "error", "message" => "Title and description are required."]);
    exit;
}

$uploadDir = "../uploads/news/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

$mediaFiles = [];

if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
    foreach ($_FILES['media']['name'] as $key => $filename) {
        $tmpName = $_FILES['media']['tmp_name'][$key];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $newName = time() . "_" . rand(1000, 9999) . "_" . basename($filename);
        $targetFile = $uploadDir . $newName;

        if (move_uploaded_file($tmpName, $targetFile)) {
            $mediaFiles[] = $newName;
        }
    }
}

$mediaString = !empty($mediaFiles) ? implode(",", $mediaFiles) : '';
date_default_timezone_set("Asia/Kolkata");
$date = date("Y-m-d H:i:s");

$stmt = $con->prepare("INSERT INTO tbl_news (title, description, image, created_at) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $title, $description, $mediaString, $date);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "News posted successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
}
?>
