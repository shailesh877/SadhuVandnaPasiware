<?php
session_start();
include("connection.php");
date_default_timezone_set('Asia/Kolkata');

$otp = trim($_POST['otp']);
$email = $_SESSION['login_email'] ?? '';

if(empty($otp) || empty($email)){
    echo "missing_data";
    exit;
}

if(!isset($_SESSION['login_otp']) || $_SESSION['login_otp'] != $otp){
    echo "invalid_otp";
    exit;
}

if(time() > $_SESSION['login_otp_expiry']){
    echo "expired_otp";
    exit;
}

// 1. Check if user exists
$check = $con->prepare("SELECT * FROM tbl_members WHERE email=? LIMIT 1");
$check->bind_param("s", $email);
$check->execute();
$res = $check->get_result();

if($res->num_rows == 1){
    // --- EXISTING USER ---
    $row = $res->fetch_assoc();
    
    if($row['status'] == 'Blocked'){
        echo "blocked";
        exit;
    }

    // Login
    $_SESSION['sadhu_user_id'] = $row['email'];
    $_SESSION['sadhu_user_name'] = $row['name'];
    setcookie('sadhu_user_id', $row['email'], time() + (30*24*60*60), "/");
    setcookie('sadhu_user_name', $row['name'], time() + (30*24*60*60), "/");
    
    echo "success_login";

} else {
    // --- NEW USER -> CREATE ACCOUNT ---
    
    $name = "New Member"; 
    // Generate a temporary unique mobile to avoid constraint violation if any
    $mobile = "TMP" . rand(1000000,9999999); 
    
    $dob = "";
    $city = "";
    $cast = "";
    $gender = "";
    $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT); // Random secure pass
    $photo = ""; 
    $date = date("Y-m-d H:i:s");
    $status = "Pending"; 

    $stmt = $con->prepare("INSERT INTO tbl_members (name, email, mobile, dob, city, cast, gender, password, profile_photo, date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $name, $email, $mobile, $dob, $city, $cast, $gender, $password, $photo, $date, $status);

    if($stmt->execute()){
        // Login new user
        $_SESSION['sadhu_user_id'] = $email;
        $_SESSION['sadhu_user_name'] = $name;
        setcookie('sadhu_user_id', $email, time() + (30*24*60*60), "/");
        setcookie('sadhu_user_name', $name, time() + (30*24*60*60), "/");

        echo "success_register";
    } else {
        echo "error_create: " . $stmt->error; 
    }
}

// Clear OTP Session
unset($_SESSION['login_otp']);
unset($_SESSION['login_otp_expiry']);
?>
