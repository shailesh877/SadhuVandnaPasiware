<?php
include 'headers.php';
include 'connection.php';

// Add admins_only column to tbl_groups
$sql = "ALTER TABLE `tbl_groups` ADD COLUMN `admins_only` TINYINT(1) DEFAULT 0";

if ($con->query($sql)) {
    echo json_encode(["status" => "success", "message" => "admins_only column added to tbl_groups."]);
} else {
    echo json_encode(["status" => "error", "message" => "Error: " . $con->error]);
}
?>
