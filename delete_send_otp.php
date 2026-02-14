<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

if (isset($_SESSION['sadhu_user_id'])) {
    $email = $_SESSION['sadhu_user_id'];
    $otp = rand(100000, 999999);

    $_SESSION['delete_otp'] = $otp;
    $_SESSION['delete_otp_expiry'] = time() + 300; // 5 minutes expiry

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@sadhuvandna.co.in';
        $mail->Password   = 'Info$%^&*756'; // app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('info@sadhuvandna.co.in', 'Sadhu Vandna Security');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Account Deletion Verification Code - Sadhu Vandna';
        $mail->Body = "
            <div style='font-family:Arial,sans-serif; padding:20px; background:#fff1f2; border:1px solid #fda4af; border-radius:12px; max-width:500px; margin:0 auto;'>
                <h2 style='color:#be123c; margin-top:0;'>Verify Deletion Request</h2>
                <p style='color:#374151;'>You have requested to delete data from your Sadhu Vandana account. Please use the following One-Time Password (OTP) to confirm this action.</p>
                
                <div style='background:white; padding:15px; border-radius:8px; text-align:center; margin:20px 0; border:1px dashed #be123c;'>
                    <span style='font-size:24px; font-weight:bold; letter-spacing:5px; color:#be123c;'>$otp</span>
                </div>
                
                <p style='font-size:13px; color:#6b7280;'>This code is valid for <b>5 minutes</b>.</p>
                <p style='font-size:13px; color:#be123c; font-weight:bold;'>If you did not request this, please change your password immediately.</p>
            </div>";

        if ($mail->send()) {
            echo "sent";
        } else {
            echo "error_send: " . $mail->ErrorInfo;
        }
    } catch (Exception $e) {
        echo "error_exception: " . $mail->ErrorInfo;
    }
} else {
    echo "invalid_session";
}
?>
