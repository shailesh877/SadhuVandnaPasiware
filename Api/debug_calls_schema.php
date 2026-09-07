<?php
include("connection.php");
$res = mysqli_query($con, "DESCRIBE tbl_calls");
$out = [];
while($row = mysqli_fetch_assoc($res)) { $out[] = $row; }
header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT);
?>
