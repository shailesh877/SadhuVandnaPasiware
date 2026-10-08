<?php
session_start();
include("connection.php");
require('../vendor/autoload.php');
include("../config.php");
use Razorpay\Api\Api;

$uesr_email= $_SESSION['sadhu_user_id'] ?? '';
$query = $con->query("SELECT * FROM tbl_members WHERE email='".$con->real_escape_string($uesr_email)."' LIMIT 1");
$res=mysqli_fetch_assoc($query);
$logged_user_id = $res['id'] ?? 0;
$sender_id = intval($_GET['sender'] ?? 0);
$receiver_id = intval($_GET['receiver'] ?? 0);
$_SESSION['sender_id'] = $sender_id;
$_SESSION['receiver_id'] = $receiver_id;

$productprice=100;
if($productprice>0)
{
    $name=$res['name'] ?? '';
    $number=$res['mobile'] ?? '';
    $amounta=$productprice*100;

    $api_key = 'rzp_test_RMXAUXty6nvaXm';
    $api_secret = 'It60ovNrbtNA6kPw0kxET8Fl';

    $api = new Api($api_key,$api_secret);
    $order = $api->order->create([
        'receipt' => '123',
        'amount' => $amounta,
        'currency' => 'INR', 
    ]);

    $order_id= $order->id;
    $_SESSION['orderid']=$order_id;

    $callback_url = "verify.php";

    echo '<script src="https://checkout.razorpay.com/v1/checkout.js"></script>';

    echo '<script>
        var options = {
            key: "' .$api_key. '",
            amount: ' . $order->amount. ',
            currency: "INR",
            name: "Sadhu Vandana",
            description: "CHAT AMOUNT",
            image: "https://vindhyastore.in/logo.png",
            order_id: "' . $order_id . '",
            prefill: {
                name: "'.$name.'",
                email: "'.$uesr_email.'",
                contact: "'.$number.'"
            },
            notes: {
                address: "Mirzapur"
            },
            theme: {
                "color": "#dd6617ff"
            },
            callback_url: "' . $callback_url . '"
        };
        var rzp = new Razorpay(options);
        rzp.open();
    </script>';
}
else {
    header("location:index.php");
}
?>
