<?php
include 'headers.php';
include 'connection.php';

$user_id = $_GET['user_id'] ?? '';

if(!$user_id){
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

$query = "SELECT * FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1";
$result = $con->query($query);

if($result->num_rows > 0){
    $profile = $result->fetch_assoc();
    echo json_encode(["status" => "success", "data" => $profile]);
} else {
    echo json_encode(["status" => "error", "message" => "Profile not found"]);
}
?>
