<?php
include 'connection.php';
header('Content-Type: text/plain');

$users = [1, 82];
echo "Token Sync Debug:\n";
foreach($users as $uid) {
    $q = $con->query("SELECT id, name, fcm_token FROM tbl_members WHERE id = '$uid'");
    if($q && $row = $q->fetch_assoc()) {
        echo "User ID: " . $row['id'] . " | Name: " . $row['name'] . " | Token: " . ($row['fcm_token'] ?: "EMPTY") . "\n";
    } else {
        echo "User ID: $uid not found.\n";
    }
}
?>
