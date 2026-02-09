<?php
// Function to send Expo Push Notifications
function sendPushNotification($userId, $title, $body, $data = null) {
    global $con;

    // Fetch user's tokens
    $stmt = $con->prepare("SELECT token FROM tbl_device_tokens WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $tokens = [];
    while ($row = $result->fetch_assoc()) {
        $tokens[] = $row['token'];
    }

    if (empty($tokens)) {
        return false;
    }

    // Expo Push API URL
    $url = 'https://exp.host/--/api/v2/push/send';

    // Prepare requests
    $requests = [];
    foreach ($tokens as $token) {
        $requests[] = [
            'to' => $token,
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'data' => $data,
        ];
    }

    // Send requests
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requests));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
?>
