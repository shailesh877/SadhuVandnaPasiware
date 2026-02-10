<?php
session_start();
include('connection.php');
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

if(isset($_POST['email'])){
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $con->prepare("SELECT id FROM tbl_members WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 0){ 
        echo json_encode(["status" => "error", "message" => "Email not registered!"]); 
        exit; 
    }

    $otp = rand(100000, 999999);
    $_SESSION['fp_otp'] = $otp;
    $_SESSION['fp_email'] = $email;
    $_SESSION['fp_otp_expiry'] = time() + 300;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        // Using the same credentials as found in Sadhuvandna-Api/forgot_send_otp.php
        $mail->Username   = 'info@sadhuvandna.co.in';
        $mail->Password   = 'Info$%^&*756';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('info@sadhuvandna.co.in', 'Sadhu Vandna');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP';
        $mail->Body    = "Your OTP for password reset is <b>$otp</b>. Valid for 5 minutes.";

        if($mail->send()){ 
            echo json_encode(["status" => "success", "message" => "OTP sent successfully"]);
        } else { 
            echo json_encode(["status" => "error", "message" => "Failed to send OTP"]);
        }
    } catch(Exception $e){ 
        echo json_encode(["status" => "error", "message" => "Mailer Error: " . $mail->ErrorInfo]); 
    }
} else {
    echo json_encode(["status" => "error", "message" => "Email required"]);
}
?>
