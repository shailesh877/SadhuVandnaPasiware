<?php
session_start();
include('connection.php');

if(isset($_POST['password'])){
    $email = $_SESSION['fp_email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $con->prepare("UPDATE tbl_members SET password=? WHERE email=?");
    $stmt->bind_param("ss",$password,$email);
    if($stmt->execute()){
        unset($_SESSION['fp_otp'], $_SESSION['fp_email'], $_SESSION['fp_otp_expiry']);
        echo "success";
    } else { echo "failed"; }
}
?>
