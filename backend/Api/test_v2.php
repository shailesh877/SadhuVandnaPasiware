<?php
include 'connection.php';
include 'push_helper.php';

$user_id = $_GET['user_id'] ?? 82;
header('Content-Type: text/plain');

echo "Sending High-Priority Test Notification via official helper...\n";
echo "Target User ID: $user_id\n";

$title = "Official Test " . date('H:i:s');
$body = "Hi, if you see this, push is working 100%!";

$result = sendExpoPushNotification($con, $user_id, $title, $body, ["test" => "data"]);

if ($result) {
    echo "SUCCESS: Expo accepted the high-priority request.\n";
    echo "Please check your phone (check notification history too).\n";
} else {
    echo "FAILED: Check push_log.txt for errors.\n";
}
?>
