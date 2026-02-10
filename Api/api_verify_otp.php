<?php
session_start();
header('Content-Type: application/json');

if (isset($_POST['otp'])) {
    $entered_otp = $_POST['otp'];
    
    if (isset($_SESSION['otp'])) {
        if (time() > $_SESSION['otp_expiry']) {
             echo json_encode(["status" => "error", "message" => "OTP Expired"]);
             unset($_SESSION['otp']);
             unset($_SESSION['otp_expiry']);
             exit;
        }

        if ($_SESSION['otp'] == $entered_otp) {
            unset($_SESSION['otp']);
            unset($_SESSION['otp_expiry']);
            echo json_encode(["status" => "success", "message" => "OTP Verified"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid OTP"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Session expired or OTP not requested"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "OTP required"]);
}
?>
