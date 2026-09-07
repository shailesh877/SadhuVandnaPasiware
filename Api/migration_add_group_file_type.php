<?php
include 'headers.php';
include 'connection.php';

$sql = "ALTER TABLE `tbl_group_messages` ADD COLUMN `file_type` VARCHAR(50) DEFAULT NULL";

if ($con->query($sql)) {
    echo json_encode(["status" => "success", "message" => "file_type column added to tbl_group_messages."]);
} else {
    echo json_encode(["status" => "error", "message" => "Error adding column: " . $con->error]);
}
?>
