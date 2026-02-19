<?php
session_start();

if (isset($_SESSION['sadhu_user_id'])) {
    $mobile = $_SESSION['sadhu_user_id'];
    $otp = rand(100000, 999999);

    $_SESSION['delete_otp'] = $otp;
    $_SESSION['delete_otp_expiry'] = time() + 300; // 5 minutes expiry

    include("connection.php");
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require 'src/PHPMailer.php';
    require 'src/SMTP.php';
    require 'src/Exception.php';

    // Fetch Email & Name
    $stmt = $con->prepare("SELECT email, name FROM tbl_members WHERE mobile=?");
    $stmt->bind_param("s", $mobile);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $email = $row['email'];
        $name = $row['name'];

        // Send Email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.hostinger.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'info@sadhuvandna.co.in';
            $mail->Password   = 'Info$%^&*756';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('info@sadhuvandna.co.in', 'Sadhu Vandana');
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = 'Account Deletion OTP - Sadhu Vandana';
            $mail->Body    = "
                <div style='font-family:Arial,sans-serif; padding:15px; border:1px solid #ddd; border-radius:10px;'>
                    <h2 style='color:#e11d48;'>Account Deletion Request</h2>
                    <p>Dear <b>$name</b>,</p>
                    <p>You have requested to delete your account (or part of it). Please use the OTP below to confirm this action.</p>
                    <p style='font-size:20px; font-weight:bold; color:#e11d48;'>$otp</p>
                    <p>If you did not request this, please ignore this email and secure your account.</p>
                </div>
            ";

            if($mail->send()){
                echo "sent";
            } else {
                echo "Mailer Error: " . $mail->ErrorInfo;
            }
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "User email not found.";
    }

} else {
    echo "invalid_session";
}
?>
