<?php
include("connection.php");
session_start();

// SESSION me email store hai
$session_email = $_SESSION['sadhu_user_id'] ?? '';

if(!$session_email){
    echo 0;
    exit;
}

// Step 1: Get user_id from email
$userQ = $con->query("SELECT id FROM tbl_members WHERE email = '$session_email' LIMIT 1");

if($userQ->num_rows == 0){
    echo 0;
    exit;
}

$user_id = $userQ->fetch_assoc()['id'];

// Step 2: Get marriage_profile_id of this user
$mpQ = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id = '$user_id' LIMIT 1");

if($mpQ->num_rows == 0){
    echo 0;
    exit;
}

$my_profile_id = $mpQ->fetch_assoc()['id'];

// Step 3: Count unread messages
$q = $con->query("
    SELECT COUNT(*) 
    FROM tbl_messages 
    WHERE receiver_id = '$my_profile_id' AND seen = 0
");

echo $q->fetch_row()[0];
?>
