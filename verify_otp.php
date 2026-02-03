<?php
session_start();

if (isset($_POST['otp'])) {
    $entered_otp = $_POST['otp'];
    if (isset($_SESSION['otp']) && $_SESSION['otp'] == $entered_otp && time() < $_SESSION['otp_expiry']) {
        unset($_SESSION['otp']);
        unset($_SESSION['otp_expiry']);
        echo "verified";
    } else {
        echo "invalid";
    }
}
?>
