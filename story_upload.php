<?php
include("connection.php");
session_start();

if(!isset($_SESSION['sadhu_user_id'])){
  header("Location: login.php");
  exit;
}

$user_email = $_SESSION['sadhu_user_id'];
$user = $con->query("SELECT * FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
$user_id = $user['id'];
$file = $_FILES['story'];

if(!$file['name']){
  header("Location: index.php");
  exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = time().'_'.rand(1000,9999).'.'.$ext;
$path = 'uploads/stories/'.$filename;
move_uploaded_file($file['tmp_name'], $path);

$type = (strpos($file['type'], 'video') !== false) ? 'video' : 'image';

$stmt = $con->prepare("INSERT INTO tbl_stories (user_id, media, type, date) VALUES (?,?,?,NOW())");
$stmt->bind_param("iss", $user_id, $path, $type);
$stmt->execute();

header("Location: index.php");
?>
