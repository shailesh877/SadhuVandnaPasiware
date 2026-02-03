<?php
include 'headers.php';
include 'connection.php';

$user_id = $_GET['user_id'] ?? '';
$receiver_profile_id = $_GET['receiver_id'] ?? '';

if(!$user_id || !$receiver_profile_id){
    echo json_encode(["status" => "error", "message" => "Invalid parameters"]);
    exit;
}

// 1. Get Sender's Marriage Profile ID
$stmt = $con->prepare("SELECT id FROM tbl_marriage_profiles WHERE user_id=? LIMIT 1");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$sender_res = $stmt->get_result();

if($sender_res->num_rows == 0){
    echo json_encode(["status" => "error", "message" => "No profile"]);
    exit;
}

$sender_profile_id = $sender_res->fetch_assoc()['id'];

// 2. Check Proposal Status
// Check outgoing: sender -> receiver
$stmt_out = $con->prepare("SELECT status FROM tbl_proposals WHERE sender_id=? AND receiver_id=? ORDER BY id DESC LIMIT 1");
$stmt_out->bind_param("ss", $sender_profile_id, $receiver_profile_id);
$stmt_out->execute();
$out_res = $stmt_out->get_result();

if($out_res->num_rows > 0){
    $status = $out_res->fetch_assoc()['status'];
    echo json_encode(["status" => "success", "proposal_status" => $status, "direction" => "outgoing"]);
    exit;
}

// Check incoming: receiver -> sender (Maybe they sent us a request?)
$stmt_in = $con->prepare("SELECT status FROM tbl_proposals WHERE sender_id=? AND receiver_id=? ORDER BY id DESC LIMIT 1");
$stmt_in->bind_param("ss", $receiver_profile_id, $sender_profile_id);
$stmt_in->execute();
$in_res = $stmt_in->get_result();

if($in_res->num_rows > 0){
    $status = $in_res->fetch_assoc()['status'];
    echo json_encode(["status" => "success", "proposal_status" => $status, "direction" => "incoming"]);
    exit;
}

echo json_encode(["status" => "success", "proposal_status" => "none"]);
?>
