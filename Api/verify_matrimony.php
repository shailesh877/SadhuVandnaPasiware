<?php
session_start();
include("connection.php");
require('vendor/autoload.php');
include("config.php");
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$user_id = intval($_SESSION['payer_user_id'] ?? 0);
$orderId = $_SESSION['matrimony_orderid'] ?? '';
$amount = $_SESSION['matrimony_amount'] ?? 500;

$api_key = 'rzp_test_RMXAUXty6nvaXm';
$api_secret = 'It60ovNrbtNA6kPw0kxET8Fl';
$api = new Api($api_key, $api_secret);

$success = true;
$error = "Payment Failed";

if (isset($_POST['razorpay_payment_id']) && isset($_POST['razorpay_signature']) && isset($_POST['razorpay_order_id'])) {
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
        // Insert success payment into wallet with payment_type = 'matrimony'
        $result = mysqli_query($con, "
            INSERT INTO tbl_wallet 
            (user_id, sender_id, receiver_id, order_id, payment_id, payment_ammount, payment_signature, status, payment_type, date)
            VALUES 
            ('$user_id', 0, 0, '$razorpay_id', '$payment_id', '$amount', '$razorpay_signature', 'success', 'matrimony', NOW())
        ");
        
        $status_msg = "Success";
        $title = "Payment Successful";
        $detail = "Your payment of ₹" . $amount . " was received. You can now return to the app and save your matrimony profile.";
    } else {
        // Insert failed payment
        mysqli_query($con, "
            INSERT INTO tbl_wallet 
            (user_id, sender_id, receiver_id, order_id, payment_id, payment_ammount, payment_signature, status, payment_type, date)
            VALUES 
            ('$user_id', 0, 0, '$razorpay_id', '$payment_id', '$amount', '$razorpay_signature', 'failed', 'matrimony', NOW())
        ");
        
        $status_msg = "Failed";
        $title = "Payment Failed";
        $detail = "There was an error verifying your signature: " . $error;
    }
} else {
    $status_msg = "Failed";
    $title = "Invalid Payment Session";
    $detail = "No transaction data received.";
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
        .btn { display: inline-block; background-color: #1a2b4c; color: white; border: none; padding: 15px 40px; font-size: 16px; font-weight: bold; border-radius: 12px; text-decoration: none; box-shadow: 0 8px 16px rgba(26, 43, 76, 0.15); width: calc(100% - 80px); transition: all 0.2s; }
        .btn:hover { background-color: #111e36; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon ' . strtolower($status_msg) . '">' . ($status_msg === 'Success' ? '✓' : '✗') . '</div>
        <h2>' . $title . '</h2>
        <p>' . $detail . '</p>
        <a href="sadhuvandna://matrimony_payment_success" class="btn">Return to Linkup App</a>
    </div>
</body>
</html>';
?>
