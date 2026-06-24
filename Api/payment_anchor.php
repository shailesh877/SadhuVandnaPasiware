<?php
session_start();
include("connection.php");
require('../vendor/autoload.php');
include("config.php");
use Razorpay\Api\Api;

$user_id = intval($_GET['user_id'] ?? 0);
if (!$user_id) {
    echo "User ID is required for payment initiation.";
    exit;
}

$_SESSION['payer_user_id'] = $user_id;

// Fetch user details
$query = $con->query("SELECT * FROM tbl_members WHERE id='$user_id' LIMIT 1");
$res = mysqli_fetch_assoc($query);

if (!$res) {
    echo "User account not found.";
    exit;
}

$name = $res['name'];
$number = $res['mobile'];
$email = $res['email'];
if (empty($email)) {
    $email = "user_" . $user_id . "@sadhuvandna.app";
}

// Fetch anchor application fee from global settings
$productprice = 100; // default fallback
$settings_q = $con->query("SELECT `value` FROM tbl_settings WHERE `key` = 'anchor_profile_fee' LIMIT 1");
if ($settings_q && $settings_q->num_rows > 0) {
    $settings_row = $settings_q->fetch_assoc();
    $productprice = intval($settings_row['value']);
}

$amounta = $productprice * 100; // in paise

// Razorpay credentials
$api_key = 'rzp_test_RMXAUXty6nvaXm';
$api_secret = 'It60ovNrbtNA6kPw0kxET8Fl';

$api = new Api($api_key, $api_secret);
$order = $api->order->create([
    'receipt' => 'anchor_' . $user_id . '_' . time(),
    'amount' => $amounta,
    'currency' => 'INR',
]);

$order_id = $order->id;
$_SESSION['anchor_orderid'] = $order_id;
$_SESSION['anchor_amount'] = $productprice;

// callback target
$callback_url = "verify_anchor.php";

echo '<!DOCTYPE html>
<html>
<head>
    <title>News Anchor Application Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #fffaf5; text-align: center; padding: 40px 20px; color: #1a2b4c; }
        .card { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(234, 88, 12, 0.08); border: 1px solid #ffeedd; }
        .logo { width: 80px; height: 80px; margin-bottom: 20px; }
        h2 { font-weight: 800; margin-bottom: 10px; }
        p { color: #64748b; font-size: 14px; margin-bottom: 30px; }
        .price { font-size: 32px; font-weight: 900; color: #ff6b00; margin-bottom: 30px; }
        button { background-color: #ff6b00; color: white; border: none; padding: 15px 40px; font-size: 16px; font-weight: bold; border-radius: 12px; cursor: pointer; box-shadow: 0 8px 16px rgba(255, 107, 0, 0.3); width: 100%; transition: all 0.2s; }
        button:hover { background-color: #e05e00; }
    </style>
</head>
<body>
    <div class="card">
        <img class="logo" src="../assets/logo.png" onerror="this.src=\'https://vindhyastore.in/logo.png\'" />
        <h2>News Anchor Application</h2>
        <p>A one-time payment is required to submit your news anchor application.</p>
        <div class="price">&#8377; ' . $productprice . '</div>
        <button onclick="startPayment()">Pay Now</button>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        var options = {
            key: "' . $api_key . '",
            amount: ' . $order->amount . ',
            currency: "INR",
            name: "Sadhu Vandna",
            description: "NEWS ANCHOR APPLICATION FEE",
            image: "https://vindhyastore.in/logo.png",
            order_id: "' . $order_id . '",
            prefill: {
                name: "' . $name . '",
                email: "' . $email . '",
                contact: "' . $number . '"
            },
            handler: function (response){
                var form = document.createElement("form");
                form.setAttribute("method", "POST");
                form.setAttribute("action", "' . $callback_url . '");

                var inputs = {
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_signature: response.razorpay_signature
                };

                for (var key in inputs) {
                    var input = document.createElement("input");
                    input.setAttribute("type", "hidden");
                    input.setAttribute("name", key);
                    input.setAttribute("value", inputs[key]);
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
            },
            theme: { color: "#ff6b00" }
        };
        var rzp = new Razorpay(options);
        function startPayment(){
            rzp.open();
        }
    </script>
</body>
</html>';
?>
