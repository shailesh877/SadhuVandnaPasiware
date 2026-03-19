<?php
// api_connect.php - Unified Connection Management
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');
include 'headers.php';
include 'connection.php';

$action = $_POST['action'] ?? '';
$user_id = $_POST['user_id'] ?? ''; // Current App User ID

if(!$user_id){
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

// Helper: Get Profile ID
function getProfileId($con, $uid){
    $stmt = $con->prepare("SELECT id FROM tbl_marriage_profiles WHERE user_id=?");
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    return ($res->num_rows > 0) ? $res->fetch_assoc()['id'] : 0;
}

$my_profile_id = getProfileId($con, $user_id);

if(!$my_profile_id){
    echo json_encode(["status" => "error", "message" => "Please create your marriage profile first."]);
    exit;
}

// 1. SEND REQUEST
if($action == 'send_request'){
    $receiver_profile_id = $_POST['receiver_id'] ?? 0; // Profile ID of person to connect
    if(!$receiver_profile_id) { echo json_encode(["status"=>"error", "message"=>"Receiver required"]); exit; }

    // Check duplication
    $chk = $con->prepare("SELECT id, status FROM tbl_proposals WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)");
    $chk->bind_param("ssss", $my_profile_id, $receiver_profile_id, $receiver_profile_id, $my_profile_id);
    $chk->execute();
    if($chk->get_result()->num_rows > 0){
        echo json_encode(["status" => "error", "message" => "Connection already exists or pending"]);
        exit;
    }

    // Insert
    // profile_id column usually stores receiver's profile ID based on other files
    $ins = $con->prepare("INSERT INTO tbl_proposals (sender_id, receiver_id, profile_id, status, date) VALUES (?, ?, ?, 'pending', NOW())");
    $ins->bind_param("sss", $my_profile_id, $receiver_profile_id, $receiver_profile_id);
    if($ins->execute()){
        echo json_encode(["status" => "success", "message" => "Request Sent"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to send"]);
    }
}

// 2. ACCEPT REQUEST
elseif($action == 'accept_request'){
    // I am the receiver. I accept request from sender_profile_id.
    $sender_profile_id = $_POST['sender_id'] ?? 0; 
    
    if(!$sender_profile_id) { echo json_encode(["status"=>"error"]); exit; }

    $upd = $con->prepare("UPDATE tbl_proposals SET status='friend' WHERE sender_id=? AND receiver_id=?");
    $upd->bind_param("ss", $sender_profile_id, $my_profile_id);
    if($upd->execute()){
        echo json_encode(["status" => "success", "message" => "Request Accepted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed"]);
    }
}

// 3. CANCEL REQUEST (I sent it, I want to cancel)
elseif($action == 'cancel_request'){
    $receiver_profile_id = $_POST['receiver_id'] ?? 0;

    $del = $con->prepare("DELETE FROM tbl_proposals WHERE sender_id=? AND receiver_id=? AND status='pending'");
    $del->bind_param("ss", $my_profile_id, $receiver_profile_id);
    if($del->execute()){
        echo json_encode(["status" => "success", "message" => "Request Cancelled"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed"]);
    }
}

// 4. REJECT REQUEST (Someone sent me, I reject)
elseif($action == 'reject_request'){
    $sender_profile_id = $_POST['sender_id'] ?? 0;

    $del = $con->prepare("DELETE FROM tbl_proposals WHERE sender_id=? AND receiver_id=?");
    $del->bind_param("ss", $sender_profile_id, $my_profile_id);
    if($del->execute()){
        echo json_encode(["status" => "success", "message" => "Request Rejected"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed"]);
    }
}

// 5. REMOVE CONNECTION (Unfriend)
elseif($action == 'remove_connection'){
    $other_profile_id = $_POST['other_id'] ?? 0;
    $other_user_id = $_POST['other_user_id'] ?? 0;

    if(!$other_profile_id && $other_user_id){
        $other_profile_id = getProfileId($con, $other_user_id);
    }

    if(!$other_profile_id){
         echo json_encode(["status" => "error", "message" => "Target invalid"]);
         exit;
    }

    // Delete regardless of who was sender/receiver
    $del = $con->prepare("DELETE FROM tbl_proposals WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)");
    $del->bind_param("ssss", $my_profile_id, $other_profile_id, $other_profile_id, $my_profile_id);
    if($del->execute()){
         echo json_encode(["status" => "success", "message" => "Connection Removed"]);
    } else {
         echo json_encode(["status" => "error", "message" => "Failed"]);
    }
}

else {
    echo json_encode(["status" => "error", "message" => "Invalid Action"]);
}
?>
