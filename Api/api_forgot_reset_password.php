<?php
session_start();
include('connection.php');
header('Content-Type: application/json');

if(isset($_POST['password'])){
    if(!isset($_SESSION['fp_email'])) {
        echo json_encode(["status" => "error", "message" => "Session expired. Please restart the process."]);
        exit;
    }

    $email = $_SESSION['fp_email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $con->prepare("UPDATE tbl_members SET password=? WHERE email=?");
    $stmt->bind_param("ss", $password, $email);
    
    if($stmt->execute()){
        unset($_SESSION['fp_otp'], $_SESSION['fp_email'], $_SESSION['fp_otp_expiry']);
        echo json_encode(["status" => "success", "message" => "Password reset successful"]);
    } else { 
        echo json_encode(["status" => "error", "message" => "Failed to update password"]); 
    }
} else {
    echo json_encode(["status" => "error", "message" => "Password required"]);
}
?>
