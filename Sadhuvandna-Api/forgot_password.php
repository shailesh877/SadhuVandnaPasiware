<?php
include 'headers.php';
include 'connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

ini_set('display_errors', 0);
error_reporting(0);

$email = $_POST['email_or_mobile'] ?? '';

if(!$email){
    echo json_encode(["status" => "error", "message" => "Email is required"]);
    exit;
}

// Check if user exists (Forgot Password flow)
$check = $con->query("SELECT id FROM tbl_members WHERE email='$email'");
if($check->num_rows == 0){
    echo json_encode(["status" => "error", "message" => "No account found with this email"]);
    exit;
}

// Create OTP Table if not exists (Safety)
$con->query("CREATE TABLE IF NOT EXISTS tbl_api_otp (
    email VARCHAR(100) PRIMARY KEY,
    otp VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Generate OTP
$otp = rand(100000, 999999);

// Send Email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'ady10112004@gmail.com';
    $mail->Password   = 'loky dacf vmdi hwvi';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('ady10112004@gmail.com', 'Sadhu Vandana');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Password Reset OTP';
    $mail->Body    = "Your OTP for password reset is <b>$otp</b>. Valid for 5 minutes.";

    if ($mail->send()) {
        // Save OTP to DB
        $stmt = $con->prepare("INSERT INTO tbl_api_otp (email, otp) VALUES (?, ?) ON DUPLICATE KEY UPDATE otp = ?");
        $stmt->bind_param("sss", $email, $otp, $otp);
        $stmt->execute();

        echo json_encode(["status" => "success", "message" => "OTP sent to your email"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to send email"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Mailer Error: " . $mail->ErrorInfo]);
}
?>
