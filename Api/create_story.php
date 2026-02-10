<?php
include 'headers.php';
include 'connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$user_id = $_POST['user_id'] ?? '';

if(!$user_id){
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

if (!isset($_FILES['image'])) {
    echo json_encode(["status" => "error", "message" => "Image required for story"]);
    exit;
}

$target_dir = "../uploads/stories/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$file_ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
$new_filename = uniqid() . '.' . $file_ext;
$target_file = $target_dir . $new_filename;

if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
    // Assuming tbl_stories has columns: user_id, image, created_at (or date)
    // If 'date' column is used instead of created_at:
    date_default_timezone_set('Asia/Kolkata');
    $date = date('Y-m-d H:i:s');
    
    $stmt = $con->prepare("INSERT INTO tbl_stories (user_id, image, date) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $user_id, $new_filename, $date); // Using $new_filename, not full path usually
    
    if($stmt->execute()){
        echo json_encode(["status" => "success", "message" => "Story uploaded"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "File upload failed"]);
}
?>
