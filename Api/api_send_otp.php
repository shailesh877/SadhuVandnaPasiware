<?php
session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

if (isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         echo json_encode(["status" => "error", "message" => "Invalid email format"]);
         exit;
    }

    $otp = rand(100000, 999999);

    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + 300; // 5 minutes expiry

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@sadhuvandna.co.in';
        $mail->Password   = 'Info$%^&*756'; // app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('info@sadhuvandna.co.in', 'Sadhu Vandna');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Registration - Sadhu Vandna';
        $mail->Body = "
            <div style='font-family:Arial,sans-serif; padding:15px; background:#fff8e1; border:1px solid #fcd34d; border-radius:10px;'>
                <h2 style='color:#c2410c;'>Your OTP for Registration</h2>
                <p>Your OTP is: <strong style='font-size:18px; color:#b45309;'>$otp</strong></p>
                <p>Valid for <b>5 minutes</b>.</p>
            </div>";

        if ($mail->send()) {
            echo json_encode(["status" => "success", "message" => "OTP sent successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to send email"]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Mailer Error: " . $mail->ErrorInfo]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Email required"]);
}
?>
