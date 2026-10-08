<?php
include 'headers.php';
include 'connection.php';

// Disable strict mode for this session to avoid errors with existing 0000-00-00 dates
$con->query("SET SESSION sql_mode = ''");

// Add is_deleted to tbl_messages if not exists
$check_msg = $con->query("SHOW COLUMNS FROM `tbl_messages` LIKE 'is_deleted'");
if ($check_msg->num_rows == 0) {
    $con->query("ALTER TABLE `tbl_messages` ADD COLUMN `is_deleted` TINYINT(1) DEFAULT 0");
}

// Add is_deleted to tbl_group_messages if not exists
$check_grp = $con->query("SHOW COLUMNS FROM `tbl_group_messages` LIKE 'is_deleted'");
if ($check_grp->num_rows == 0) {
    $con->query("ALTER TABLE `tbl_group_messages` ADD COLUMN `is_deleted` TINYINT(1) DEFAULT 0");
}

echo json_encode(["status" => "success", "message" => "Migration check completed successfully."]);
?>
