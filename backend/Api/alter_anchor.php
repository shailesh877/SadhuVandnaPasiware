<?php
include 'headers.php';
include 'connection.php';
$query1 = $con->query("ALTER TABLE tbl_anchor_applications ADD COLUMN state VARCHAR(100) DEFAULT NULL AFTER education");
$query2 = $con->query("ALTER TABLE tbl_anchor_applications ADD COLUMN district VARCHAR(100) DEFAULT NULL AFTER state");
if($query1 || $query2) {
    echo "Columns added successfully";
} else {
    echo "Error: " . $con->error;
}
?>
