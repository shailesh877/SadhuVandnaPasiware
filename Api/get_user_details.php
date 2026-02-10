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

$user_id = $_GET['user_id'] ?? '';
if(!$user_id){
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

$stmt = $con->prepare("SELECT id, name, email, mobile, city, dob, cast, gender, profile_photo, status FROM tbl_members WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){
    $data = $result->fetch_assoc();
    echo json_encode(["status" => "success", "data" => $data]);
} else {
    echo json_encode(["status" => "error", "message" => "User not found"]);
}
?>
