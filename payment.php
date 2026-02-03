<?php
session_start();
include("connection.php");
require('vendor/autoload.php');
include("config.php");
use Razorpay\Api\Api;
$uesr_email= $_SESSION['sadhu_user_id'] ?? '';
$query = $con->query("SELECT * FROM tbl_members WHERE email='".$con->real_escape_string($uesr_email)."' LIMIT 1");
$res=mysqli_fetch_assoc($query);
$logged_user_id = $res['id'] ?? 0;
$sender_id = intval($_GET['sender'] ?? 0);
$receiver_id = intval($_GET['receiver'] ?? 0);
$_SESSION['sender_id'] = $sender_id;
$_SESSION['receiver_id'] = $receiver_id;
// echo "Sender ID: $sender_id, Receiver ID: $receiver_id";
// echo '<title>Sadhu Vandana | Payment</title>';
// echo "user Email: $uesr_email ";
// echo "Logged User ID: $logged_user_id ";
$productprice=100;
if($productprice>0)
{
    $name=$res['name'];
    // $email=$_POST['email'];
    $number=$res['mobile'];
    $amounta=$productprice*100;
// require_once _DIR_ . "/../../razorpay-php/Razorpay.php";
// Initialize Razorpay with key and secret.
$api_key = 'rzp_test_RMXAUXty6nvaXm';
$api_secret = 'It60ovNrbtNA6kPw0kxET8Fl';
//
$api = new Api($api_key,$api_secret);
$order = $api->order->create([
'receipt' => '123',
 'amount' => $amounta,
 'currency' => 'INR', 
// 'notes'=> array('key1'=> 'value3','key2'=> 'value2')
]);
// get the orderid
$order_id= $order->id;
// session_start();
$_SESSION['orderid']=$order_id;


// checkout code.
// Set your callback URL
$callback_url = "verify.php";

// Include Razorpay Checkout.js library
echo '<script src="https://checkout.razorpay.com/v1/checkout.js"></script>';

// Create a payment button with Checkout.js
// echo '<button onclick="startPayment()">Pay with Razorpay</button>';

// Add a script to handle the payment
echo '<script>
    // function startPayment() {
    // var amount=document.getElementById("amount").value;
        var options = {
            key: "' .$api_key. '", // Enter the Key ID generated from the Dashboard
            amount: ' . $order->amount. ', // Amount is in currency subunits. Default currency is INR. Hence, 50000 refers to 50000 paise
            currency: "INR",
            name: "Sadhu Vandana",
            description: "CHAT AMOUNT",
            image: "https://vindhyastore.in/logo.png",
            order_id: "' . $order_id . '", // This is a sample Order ID. Pass the id obtained in the response of Step 1
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
    // }
</script>';
}
else{
    header("location:index.php");
}
?>