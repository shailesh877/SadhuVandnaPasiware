<?php
include 'headers.php';
include 'connection.php';

$sql = "ALTER TABLE `tbl_groups` ADD COLUMN `invite_code` VARCHAR(50) UNIQUE DEFAULT NULL";

if ($con->query($sql)) {
    echo json_encode(["status" => "success", "message" => "invite_code column added successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Error adding column: " . $con->error]);
}
?>
