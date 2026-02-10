<?php
include 'headers.php';
include 'connection.php';

$user_id = intval($_POST['user_id'] ?? $_GET['user_id'] ?? 0);
$receiver_id = intval($_POST['receiver_id'] ?? $_GET['receiver_id'] ?? 0); // Marriage Profile ID

if(!$user_id){
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

// Get My Profile ID
$mp = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1")->fetch_assoc();
$my_profile_id = $mp['id'] ?? 0;

if(!$my_profile_id){
    echo json_encode(["status" => "error", "message" => "Marriage Profile not found"]);
    exit;
}

// Check Messages History first
$msg_check = $con->query("
    SELECT id FROM tbl_messages
    WHERE 
        (sender_id = '$my_profile_id' AND receiver_id = '$receiver_id')
        OR
        (sender_id = '$receiver_id' AND receiver_id = '$my_profile_id')
    LIMIT 1
");

if($msg_check->num_rows > 0){
    echo json_encode(["status" => "success", "paid" => true, "my_profile_id" => $my_profile_id, "reason" => "history"]);
    exit;
}
// Check Wallet
$check = $con->query("
    SELECT id FROM tbl_wallet
    WHERE 
        (
            (sender_id = '$my_profile_id' AND receiver_id = '$receiver_id')
            OR
            (sender_id = '$receiver_id' AND receiver_id = '$my_profile_id')
        )
        AND status = 'success'
    LIMIT 1
");

if($check->num_rows > 0){
    echo json_encode(["status" => "success", "paid" => true, "my_profile_id" => $my_profile_id]);
} else {
    echo json_encode(["status" => "success", "paid" => false, "my_profile_id" => $my_profile_id, "payment_url" => "payment.php?sender=$my_profile_id&receiver=$receiver_id"]);
}
?>
