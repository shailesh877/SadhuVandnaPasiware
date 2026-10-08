<?php
include 'headers.php';
include 'connection.php';
$query = $con->query("SHOW COLUMNS FROM tbl_anchor_applications");
$cols = [];
while($row = $query->fetch_assoc()) {
    $cols[] = $row['Field'];
}
echo json_encode($cols);
?>
