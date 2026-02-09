<?php
session_start();
include("connection.php");
require('vendor/autoload.php');
include("config.php");
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$uesr_email= $_SESSION['sadhu_user_id'] ?? '';
$query = $con->query("SELECT id FROM tbl_members WHERE email='".$con->real_escape_string($uesr_email)."' LIMIT 1");
$res=mysqli_fetch_assoc($query);
$logged_user_id = $res['id'] ?? 0;
$sender_id = intval($_SESSION['sender_id'] ?? 0);
$receiver_id = intval($_SESSION['receiver_id'] ?? 0);
$api_key = 'rzp_test_RMXAUXty6nvaXm';
$api_secret = 'It60ovNrbtNA6kPw0kxET8Fl';
$api = new Api($api_key,$api_secret);

$success = true;
$error = "Payment Failed";
if(isset($_POST['razorpay_payment_id']) && isset($_POST['razorpay_signature']) && isset($_POST['razorpay_order_id']))
{
// $id=$_SESSION['OID'];
$orderId=$_SESSION['orderid'];
$payment_id=$_POST['razorpay_payment_id'];
$razorpay_signature=$_POST['razorpay_signature'];
$razorpay_id=$_POST['razorpay_order_id'];
    try
    {
        // Please note that the razorpay order ID must
        // come from a trusted source (session here, but
        // could be database or something else)
        $attributes = array(
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $payment_id,
            'razorpay_signature' => $razorpay_signature
        );
        $api->utility->verifyPaymentSignature($attributes);
    }
    catch(SignatureVerificationError $e)
    {
        $success = false;
        $error = 'Razorpay Error : ' . $e->getMessage();
    }

if ($success)
{
    require "connection.php";
    $result=mysqli_query($con,"INSERT INTO tbl_wallet (user_id, sender_id, receiver_id, order_id, payment_id, payment_ammount, payment_signature, status, date)
     VALUES ('$logged_user_id', '$sender_id', '$receiver_id', '$razorpay_id', '$payment_id', '100', '$razorpay_signature','success', now())");
    if($result)
    {
    // session_destroy();
echo "<script>
    alert('Payment Success.');

    window.location.href = 'message.php?sender_id=$sender_id&receiver_id=$receiver_id';
</script>";

//    header("location:reg.php");
// echo "Hi";
}
else
{
    echo '<script>alert("Order Failed try again .");window.location.href="account.php";</script>';
    // header("location:reg.php"); 
}
}
else
{
    require "connection.php";
    $result=mysqli_query($con,"INSERT INTO tbl_wallet (user_id,order_id, payment_id, payment_ammount, payment_signature, status, date)
     VALUES ('$logged_user_id','$razorpay_id', '$payment_id', '100', '$razorpay_signature','failed', now())");
    if($result)
    {
    echo "<script>
    alert('Payment Failed.');

    window.location.href = 'message.php?sender_id=$sender_id&receiver_id=$receiver_id';
</script>";
}
else
{
    header("location:index.php"); 
}
}
}
else
{
    // header("location:../index.php");
}
