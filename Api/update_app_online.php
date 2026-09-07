<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include("connection.php");
date_default_timezone_set("Asia/Kolkata");
$con->query("SET time_zone = '+05:30'");

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? $_POST['user_id'] ?? 0;

if($user_id){
    // Update tbl_members last_active
    // We also update tbl_marriage_profiles if linked, but tbl_members is the auth source
    $con->query("UPDATE tbl_members SET last_active=NOW() WHERE id='$user_id'");
    
    // Also update marriage profile if exists (for easier joining in other queries)
    $con->query("UPDATE tbl_marriage_profiles SET last_active=NOW() WHERE user_id='$user_id'");
    
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error', 'message'=>'No user_id']);
}
?>
