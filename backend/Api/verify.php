<?php
session_start();
include("connection.php");
require('../vendor/autoload.php');
include("../config.php");
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$uesr_email = $_SESSION['sadhu_user_id'] ?? '';
$query = $con->query("SELECT id FROM tbl_members WHERE email='".$con->real_escape_string($uesr_email)."' LIMIT 1");
$res = mysqli_fetch_assoc($query);
$logged_user_id = $res['id'] ?? 0;
$sender_id = intval($_SESSION['sender_id'] ?? 0);
$receiver_id = intval($_SESSION['receiver_id'] ?? 0);

$api_key = 'rzp_test_RMXAUXty6nvaXm';
$api_secret = 'It60ovNrbtNA6kPw0kxET8Fl';
$api = new Api($api_key, $api_secret);

$success = true;
$error = "Payment Verification Failed";

if (isset($_POST['razorpay_payment_id']) && isset($_POST['razorpay_signature']) && isset($_POST['razorpay_order_id'])) {
    $orderId = $_SESSION['orderid'] ?? $_POST['razorpay_order_id'];
    $payment_id = $_POST['razorpay_payment_id'];
    $razorpay_signature = $_POST['razorpay_signature'];
    $razorpay_id = $_POST['razorpay_order_id'];

    try {
        $attributes = array(
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $payment_id,
            'razorpay_signature' => $razorpay_signature
        );
        $api->utility->verifyPaymentSignature($attributes);
    } catch (SignatureVerificationError $e) {
        $success = false;
        $error = 'Razorpay Error : ' . $e->getMessage();
    }

    if ($success) {
        mysqli_query($con, "
            INSERT INTO tbl_wallet 
            (user_id, sender_id, receiver_id, order_id, payment_id, payment_ammount, payment_signature, status, payment_type, date)
            VALUES 
            ('$logged_user_id', '$sender_id', '$receiver_id', '$razorpay_id', '$payment_id', '100', '$razorpay_signature', 'success', 'chat', NOW())
        ");
        
        $status_msg = "Success";
        $title = "Payment Successful!";
        $detail = "Your chat payment of ₹100 was received successfully. You can now return to the app and start chatting!";
    } else {
        mysqli_query($con, "
            INSERT INTO tbl_wallet 
            (user_id, sender_id, receiver_id, order_id, payment_id, payment_ammount, payment_signature, status, payment_type, date)
            VALUES 
            ('$logged_user_id', '$sender_id', '$receiver_id', '$razorpay_id', '$payment_id', '100', '$razorpay_signature', 'failed', 'chat', NOW())
        ");
        
        $status_msg = "Failed";
        $title = "Payment Failed";
        $detail = "There was an error verifying your transaction: " . $error;
    }
} else {
    $status_msg = "Failed";
    $title = "Invalid Payment Session";
    $detail = "No payment response data was received.";
}

echo '<!DOCTYPE html>
<html>
<head>
    <title>Payment ' . $status_msg . '</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #fffaf5; text-align: center; padding: 40px 20px; color: #1a2b4c; }
        .card { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(234, 88, 12, 0.08); border: 1px solid #ffeedd; }
        .icon { font-size: 64px; margin-bottom: 20px; }
        .success { color: #10b981; }
        .failed { color: #ef4444; }
        h2 { font-weight: 800; margin-bottom: 10px; }
        p { color: #64748b; font-size: 14px; margin-bottom: 30px; line-height: 1.5; }
        .btn { display: inline-block; background-color: #ea580c; color: white; border: none; padding: 15px 40px; font-size: 16px; font-weight: bold; border-radius: 12px; text-decoration: none; box-shadow: 0 8px 16px rgba(234, 88, 12, 0.25); width: calc(100% - 80px); transition: all 0.2s; }
        .btn:hover { background-color: #c2410c; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon ' . strtolower($status_msg) . '">' . ($status_msg === 'Success' ? '✓' : '✗') . '</div>
        <h2>' . $title . '</h2>
        <p>' . $detail . '</p>
        <a href="sadhuvandna://chat_payment_success" class="btn">Return to Linkup App</a>
    </div>
</body>
</html>';
?>
