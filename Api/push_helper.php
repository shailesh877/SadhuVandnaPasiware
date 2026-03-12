<?php
/**
 * Global Push Notification Helper
 * Uses Expo Push API to send notifications to devices
 */

function sendExpoPushNotification($con, $receiver_id, $title, $body, $data = []) {
    // 1. Get FCM Token from tbl_members
    $tQ = $con->query("SELECT fcm_token FROM tbl_members WHERE id = '$receiver_id' LIMIT 1");
    if ($tQ && $tQ->num_rows > 0) {
        $token = $tQ->fetch_assoc()['fcm_token'];
        
        if ($token && strpos($token, 'ExponentPushToken') === 0) {
            // 2. Prepare Expo Push Notification Paylaod
            $url = "https://exp.host/--/api/v2/push/send";
            $payload = [
                "to" => $token,
                "title" => $title,
                "body" => $body,
                "data" => $data,
                "sound" => "default",
                "priority" => "high",
                "channelId" => "default"
            ];
            
            $options = [
                "http" => [
                    "header"  => "Content-type: application/json",
                    "method"  => "POST",
                    "content" => json_encode($payload)
                ]
            ];
            
            // 3. Send Notification securely ignoring errors so it doesn't break main API response
            $context  = stream_context_create($options);
            @file_get_contents($url, false, $context);
            return true;
        }
    }
    return false;
}
