<?php
include 'connection.php';
include 'push_helper.php';

$user_id = $_GET['user_id'] ?? 0;
if (!$user_id) {
    echo "Usage: ?user_id=123";
    exit;
}

// Custom send function with error capture
function sendTestPush($con, $receiver_id, $title, $body) {
    $tQ = $con->query("SELECT fcm_token FROM tbl_members WHERE id = '$receiver_id' LIMIT 1");
    if ($tQ && $tQ->num_rows > 0) {
        $token = $tQ->fetch_assoc()['fcm_token'];
        echo "Token found: " . ($token ?: "NULL") . "\n";
        
        if ($token && strpos($token, 'ExponentPushToken') === 0) {
            $url = "https://exp.host/--/api/v2/push/send";
            $payload = [
                "to" => $token,
                "title" => $title,
                "body" => $body,
                "sound" => "default"
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
            
            if ($err) echo "CURL Error: " . $err . "\n";
            else echo "Response from Expo: " . $response . "\n";
            return $response;
        } else {
            echo "Invalid token format.\n";
        }
    } else {
        echo "User not found or no token entry.\n";
    }
    return false;
}

header('Content-Type: text/plain');
echo "Testing push for User ID: $user_id\n";
sendTestPush($con, $user_id, "Test Title", "Test Body at " . date('H:i:s'));
?>
