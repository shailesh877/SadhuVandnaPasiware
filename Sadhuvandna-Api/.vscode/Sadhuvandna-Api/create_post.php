<?php
include 'headers.php';
include 'connection.php';

$user_id = $_POST['user_id'] ?? '';
$title = $_POST['title'] ?? ''; // Optional, maybe caption
$description = $_POST['description'] ?? '';
// File upload handling ideally, but for now just text

if(!$user_id || !$description){
    echo json_encode(["status" => "error", "message" => "Content required"]);
    exit;
}

date_default_timezone_set('Asia/Kolkata');
$date = date('d-m-Y H:i:s');
// Simplified table: tbl_posts (id, user_id, description, image, date)
// Assuming table structure from common WordPress-like or custom PHP apps
// If tbl_posts doesn't exist, this fails. Based on `post.php` usage it should exist.

$stmt = $con->prepare("INSERT INTO tbl_posts (user_id, description, date) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $user_id, $description, $date);

if($stmt->execute()){
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed"]);
}
?>
