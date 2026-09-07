<?php
include 'connection.php';
$res = $con->query("SELECT * FROM tbl_calls ORDER BY id DESC LIMIT 10");
$calls = [];
if($res) while($row = $res->fetch_assoc()) $calls[] = $row;
header('Content-Type: application/json');
echo json_encode(["status" => "success", "last_calls" => $calls], JSON_PRETTY_PRINT);
?>
