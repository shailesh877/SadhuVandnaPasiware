<?php
session_start();
include 'connection.php'; // your database connection

if(!isset($_SESSION['sadhu_user_id'])){
    die("Login required!");
}

$user_email = $_SESSION['sadhu_user_id']; // email stored in session
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
$link = isset($_POST['link']) ? trim($_POST['link']) : '';

// Fetch numeric user_id from email
$stmt = $con->prepare("SELECT id FROM tbl_members WHERE email=? LIMIT 1");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows == 0){
    die("User not found!");
}

$user = $res->fetch_assoc();
$user_id = $user['id']; // numeric ID

// Upload folder
$uploadDir = "uploads/posts/";
$mediaFiles = [];

if(!empty($_FILES['media']['name'][0])) {
    foreach($_FILES['media']['name'] as $key => $filename) {
        $tmpName = $_FILES['media']['tmp_name'][$key];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $newName = uniqid('post_').'.'.$ext;
        $targetFile = $uploadDir . $newName;

        // Make sure folder exists
        if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if(move_uploaded_file($tmpName, $targetFile)){
            $mediaFiles[] = $newName;
        }
    }
}

// Convert array to comma-separated string
$mediaString = !empty($mediaFiles) ? implode(",", $mediaFiles) : NULL;
date_default_timezone_set("Asia/Kolkata");
$date = date("Y-m-d H:i:s");
// Insert into database
$stmt = $con->prepare("INSERT INTO tbl_posts (user_id, status, media,link,created_at) VALUES (?, ?, ?,?,?)");
$stmt->bind_param("issss", $user_id, $status, $mediaString,$link,$date);

if($stmt->execute()){
    header("Location: index?success=1"); // redirect to feed or homepage
    exit;
} else {
    echo "Error: ".$stmt->error;
}
?>
