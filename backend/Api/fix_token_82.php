<?php
include 'connection.php';
$newToken = 'ExponentPushToken[cW0vlcAC3Vv8t_WhG3_VJN]';
$userId = 82;

$con->query("UPDATE tbl_members SET fcm_token = '$newToken' WHERE id = '$userId'");
echo "Updated User $userId with Token $newToken. affected: " . $con->affected_rows;
?>
