<?php
include("connection.php");

$sql = "ALTER TABLE tbl_stories ADD COLUMN music_id INT DEFAULT NULL";

if ($con->query($sql)) {
    echo "Success: music_id column added to tbl_stories";
} else {
    echo "Error: " . $con->error;
}
?>
