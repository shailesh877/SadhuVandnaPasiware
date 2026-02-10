<?php
include 'headers.php';
include 'connection.php';

$user_id = $_POST['user_id'] ?? '';
$receiver_profile_id = $_POST['receiver_id'] ?? '';

if(!$user_id || !$receiver_profile_id){
    echo json_encode(["status" => "error", "message" => "Invalid parameters"]);
    exit;
}

// 1. Get Sender's Marriage Profile ID
// In the app, we send user_id (member ID). We need to find their Marriage Profile ID.
$stmt = $con->prepare("SELECT id FROM tbl_marriage_profiles WHERE user_id=? LIMIT 1");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$sender_res = $stmt->get_result();

if($sender_res->num_rows == 0){
    echo json_encode(["status" => "error", "message" => "Please create your marriage profile first."]);
    exit;
}

$sender_profile_id = $sender_res->fetch_assoc()['id'];

// 2. Check for existing proposal
// We need to check both directions? Usually sender->receiver. 
// Website logic: WHERE (sender_id='$sender_id' AND receiver_id='$receiver_id' AND status='pending')
$stmt_check = $con->prepare("SELECT id, status FROM tbl_proposals WHERE sender_id=? AND receiver_id=?");
$stmt_check->bind_param("ss", $sender_profile_id, $receiver_profile_id);
$stmt_check->execute();
$check_res = $stmt_check->get_result();

if($check_res->num_rows > 0){
    $row = $check_res->fetch_assoc();
    echo json_encode(["status" => "error", "message" => "Request already sent (Status: " . $row['status'] . ")"]);
    exit;
}

// 3. Insert Proposal
// Logic from website: sender_id, receiver_id, profile_id, status
// 'profile_id' column in tbl_proposals seems redundant or refers to something else? 
// In website: VALUES ('$sender_id','$receiver_id','$profile_id','pending')
// $sender_id = sender's profile id.
// $receiver_id = receiver's profile id (from $_GET['to']).
// $profile_id = receiver's profile id (from $_GET['profile_id']).
// It seems receiver_id and profile_id are same in website usage?
// Let's assume profile_id column is also receiver's profile id or checking logic.
// Safest bet: Insert same Value for receiver_id and profile_id if they are the same based on usage.
// Or just insert.
$stmt_ins = $con->prepare("INSERT INTO tbl_proposals (sender_id, receiver_id, profile_id, status) VALUES (?, ?, ?, 'pending')");
$stmt_ins->bind_param("sss", $sender_profile_id, $receiver_profile_id, $receiver_profile_id); // Assuming profile_id = receiver_id
if($stmt_ins->execute()){
    echo json_encode(["status" => "success", "message" => "Proposal sent successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}
?>
