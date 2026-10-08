<?php
include 'connection.php';
$res = $con->query("DESCRIBE tbl_posts");
$cols = [];
while($row = $res->fetch_assoc()) { $cols[] = $row; }
header('Content-Type: application/json');
echo json_encode($cols, JSON_PRETTY_PRINT);
?>
