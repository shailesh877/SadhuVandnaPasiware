<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

if (isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $otp = rand(100000, 999999);

    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + 300; // 5 minutes expiry

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ady10112004@gmail.com';
        $mail->Password   = 'loky dacf vmdi hwvi'; // app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('ady10112004@gmail.com', 'Sadhu Vandana');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Registration - Sadhu Vandana';
        $mail->Body = "
            <div style='font-family:Arial,sans-serif; padding:15px; background:#fff8e1; border:1px solid #fcd34d; border-radius:10px;'>
                <h2 style='color:#c2410c;'>Your OTP for Registration</h2>
                <p>Your OTP is: <strong style='font-size:18px; color:#b45309;'>$otp</strong></p>
                <p>Valid for <b>5 minutes</b>.</p>
            </div>";

        if ($mail->send()) {
            echo "sent";
        } else {
            echo "error";
        }
    } catch (Exception $e) {
        echo "error";
    }
} else {
    echo "invalid";
}
?>
