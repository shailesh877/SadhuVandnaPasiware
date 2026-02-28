<?php
include("connection.php");
$res = $con->query("DESCRIBE tbl_messages");
while($row = $res->fetch_assoc()) echo $row['Field'] . " - " . $row['Type'] . "\n";
?>
