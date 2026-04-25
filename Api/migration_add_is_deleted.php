<?php
include 'headers.php';
include 'connection.php';

// Disable strict mode for this session to avoid errors with existing 0000-00-00 dates
$con->query("SET SESSION sql_mode = ''");

// Add is_deleted to tbl_messages
$con->query("ALTER TABLE `tbl_messages` ADD COLUMN `is_deleted` TINYINT(1) DEFAULT 0");

// Add is_deleted to tbl_group_messages
$con->query("ALTER TABLE `tbl_group_messages` ADD COLUMN `is_deleted` TINYINT(1) DEFAULT 0");

echo json_encode(["status" => "success", "message" => "is_deleted columns added successfully."]);
?>
