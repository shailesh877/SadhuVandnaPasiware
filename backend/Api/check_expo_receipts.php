<?php
/**
 * Check Expo Push Receipt Status
 * This script takes an Expo Push Ticket ID and checks if FCM actually delivered it.
 */

header('Content-Type: text/plain');

$ticket_id = $_GET['id'] ?? '';
if (!$ticket_id) {
    echo "Usage: ?id=xxxx-xxxx-xxxx-xxxx\n";
    echo "You can find IDs in your push_log.txt\n";
    exit;
}

$url = "https://exp.host/--/api/v2/push/getReceipts";
$payload = ["ids" => [$ticket_id]];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

echo "Checking Receipt for ID: $ticket_id\n\n";
echo "Response from Expo:\n";
echo $response;
?>
