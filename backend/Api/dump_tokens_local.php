<?php
include("connection.php");
$res = $con->query("SELECT id, name, fcm_token FROM tbl_members WHERE fcm_token != '' LIMIT 10");
$log = "";
while($row = $res->fetch_assoc()) {
    $log .= "ID: {$row['id']} | Name: {$row['name']} | Token: {$row['fcm_token']}\n";
}
file_put_contents("token_samples.txt", $log);
echo "Done";
?>
