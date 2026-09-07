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

$proposal_id = $_POST['proposal_id'] ?? '';
$action = $_POST['action'] ?? ''; // 'accept' or 'reject'

if(!$proposal_id || !$action){
    echo json_encode(["status" => "error", "message" => "Missing params"]);
    exit;
}

$status = ($action === 'accept') ? 'friend' : 'rejected';

$stmt = $con->prepare("UPDATE tbl_proposals SET status=? WHERE id=?");
$stmt->bind_param("ss", $status, $proposal_id);

if($stmt->execute()){
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed"]);
}
?>
