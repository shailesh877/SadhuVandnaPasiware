<?php
session_start();
header('Content-Type: application/json');

if(isset($_POST['otp'])){
    $otp = trim($_POST['otp']);
    
    if(isset($_SESSION['fp_otp']) && $_SESSION['fp_otp'] == $otp && time() < $_SESSION['fp_otp_expiry']){
        echo json_encode(["status" => "success", "message" => "OTP Verified"]);
    } else { 
        echo json_encode(["status" => "error", "message" => "Invalid or Expired OTP"]); 
    }
} else {
    echo json_encode(["status" => "error", "message" => "OTP required"]);
}
?>
