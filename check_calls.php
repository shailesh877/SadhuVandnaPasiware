<?php
include("connection.php");
echo "CALLS:\n";
$res = $con->query("DESC tbl_calls");
while($row = $res->fetch_assoc()) print_r($row);
?>
