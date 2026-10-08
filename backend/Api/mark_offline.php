<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include("connection.php");
date_default_timezone_set("Asia/Kolkata");
$con->query("SET time_zone = '+05:30'");

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data["user_id"] ?? $_POST["user_id"] ?? 0;

if($user_id){
    require_once 'QueueManager.php';
    QueueManager::pushJob('update_last_seen', [
        'user_id' => $user_id
    ], "last_seen_{$user_id}", 60);
    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error", "message"=>"No user_id"]);
}
?>