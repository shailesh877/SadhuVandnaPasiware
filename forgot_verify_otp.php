<?php
session_start();
if(isset($_POST['otp'])){
    $otp = trim($_POST['otp']);
    if(isset($_SESSION['fp_otp']) && $_SESSION['fp_otp']==$otp && time()<$_SESSION['fp_otp_expiry']){
        echo "verified";
    } else { echo "invalid"; }
}
?>
