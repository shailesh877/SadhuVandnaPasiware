<?php
include 'connection.php';
header('Content-Type: text/plain');
$id = 82;
$q = $con->query("SELECT fcm_token FROM tbl_members WHERE id = $id");
if($row = $q->fetch_assoc()) {
    echo "User 82 Token: " . ($row['fcm_token'] ?? 'NULL') . "\n";
} else {
    echo "User 82 not found.\n";
}
?>
