<?php
/**
 * Global Push Notification Helper
 * Uses Expo Push API to send notifications to devices
 */

function sendExpoPushNotification($con, $receiver_id, $title, $body, $data = [])
{
    // 1. Get FCM Token from tbl_members
    $tQ = $con->query("SELECT fcm_token FROM tbl_members WHERE id = '$receiver_id' LIMIT 1");
    if ($tQ && $tQ->num_rows > 0) {
        $token = $tQ->fetch_assoc()['fcm_token'];

        if ($token && strpos($token, 'ExponentPushToken') === 0) {
            $url = "https://exp.host/--/api/v2/push/send";
            $payload = [
                "to" => $token,
                "title" => $title,
                "body" => $body,
                "data" => $data,
                "sound" => $data['sound'] ?? "default",
                "priority" => "high",
                "channelId" => $data['channelId'] ?? "default"
            ];

            $json = json_encode($payload);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Often needed on some shared hosts

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Log for debugging
            $log = date('Y-m-d H:i:s') . " | User: $receiver_id | Token: $token | Status: $httpCode | Error: $curlError | Resp: $response\n";
            file_put_contents("push_log.txt", $log, FILE_APPEND);

            return ($httpCode === 200);
        }
        else {
            file_put_contents("push_log.txt", date('Y-m-d H:i:s') . " | User: $receiver_id | Invalid Token: $token\n", FILE_APPEND);
        }
    }
    else {
        file_put_contents("push_log.txt", date('Y-m-d H:i:s') . " | User: $receiver_id | User not found or no token\n", FILE_APPEND);
    }
    return false;
}
