<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

if (isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "invalid_email";
        exit;
    }

    $otp = rand(100000, 999999);

    $_SESSION['login_otp'] = $otp;
    $_SESSION['login_email'] = $email;
    $_SESSION['login_otp_expiry'] = time() + 300; // 5 minutes

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@sadhuvandna.co.in';
        $mail->Password   = 'Info$%^&*756'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('info@sadhuvandna.co.in', 'Sadhu Vandna Login');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your Login OTP - Sadhu Vandna';
        $mail->Body = "
            <div style='font-family:Arial,sans-serif; padding:20px; text-align:center;'>
                <h2 style='color:#ea580c;'>Login Verification</h2>
                <p>Use the OTP below to log in or create your account.</p>
                <div style='font-size:24px; font-weight:bold; letter-spacing:5px; color:#ea580c; margin:20px 0;'>
                    $otp
                </div>
                <p style='color:#666; font-size:12px;'>Valid for 5 minutes.</p>
            </div>";

        if ($mail->send()) {
            echo "sent";
        } else {
            echo "error_send";
        }
    } catch (Exception $e) {
        echo "error_mailer";
    }
} else {
    echo "missing_email";
}
?>
