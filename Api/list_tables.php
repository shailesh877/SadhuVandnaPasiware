<?php
include 'connection.php';
$result = $con->query("SHOW TABLES");
while($row = $result->fetch_array()){
    echo $row[0] . "\n";
}
?>
