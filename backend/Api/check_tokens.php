<?php
include 'connection.php';
header('Content-Type: text/plain');

$u1 = $con->query("SELECT id, name, fcm_token FROM tbl_members WHERE id = 1")->fetch_assoc();
$u82 = $con->query("SELECT id, name, fcm_token FROM tbl_members WHERE id = 82")->fetch_assoc();

echo "User 1: " . ($u1['fcm_token'] ?? 'NONE') . "\n";
echo "User 82: " . ($u82['fcm_token'] ?? 'NONE') . "\n";
?>
