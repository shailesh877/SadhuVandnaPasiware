<?php
include("connection.php");

header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Handle both JSON and FormData
$json = file_get_contents('php://input');
$data_input = json_decode($json, true);

$user_id = intval($_REQUEST['user_id'] ?? $data_input['user_id'] ?? 0);
$receiver_id = intval($_REQUEST['receiver_id'] ?? $data_input['receiver_id'] ?? 0);

if (!$user_id || !$receiver_id) {
    echo json_encode(["status" => "success", "proposal_status" => "none"]);
    exit;
}

// Get Marriage Profile ID for sender
$mq = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1");
$my_profile_id = ($mq && $mq->num_rows > 0) ? $mq->fetch_assoc()['id'] : 0;

if (!$my_profile_id) {
    echo json_encode(["status" => "success", "proposal_status" => "none"]);
    exit;
}

$pq = $con->query("SELECT status, sender_id FROM tbl_proposals WHERE (sender_id='$my_profile_id' AND receiver_id='$receiver_id') OR (sender_id='$receiver_id' AND receiver_id='$my_profile_id') LIMIT 1");
$status = 'none';

if ($pq && $pq->num_rows > 0) {
    $p = $pq->fetch_assoc();
    $status = $p['status'];
    if ($status == 'pending') {
        $status = ($p['sender_id'] == $my_profile_id) ? 'pending' : 'received';
    }
}

echo json_encode(["status" => "success", "proposal_status" => $status]);
?>
