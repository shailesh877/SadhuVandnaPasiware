<?php
include 'headers.php';
include 'connection.php';

// Disable strict mode for this session to avoid SQL strict mode issues
$con->query("SET SESSION sql_mode = ''");

// 1. Create tbl_settings table if it doesn't exist
$con->query("
    CREATE TABLE IF NOT EXISTS `tbl_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `key` VARCHAR(100) UNIQUE NOT NULL,
        `value` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 2. Insert default settings if they don't exist
$check_fee = $con->query("SELECT id FROM `tbl_settings` WHERE `key` = 'matrimony_profile_fee'");
if ($check_fee && $check_fee->num_rows == 0) {
    $con->query("INSERT INTO `tbl_settings` (`key`, `value`) VALUES ('matrimony_profile_fee', '500')");
    echo "Default matrimony_profile_fee setting inserted.<br>";
}

$check_anchor_fee = $con->query("SELECT id FROM `tbl_settings` WHERE `key` = 'anchor_profile_fee'");
if ($check_anchor_fee && $check_anchor_fee->num_rows == 0) {
    $con->query("INSERT INTO `tbl_settings` (`key`, `value`) VALUES ('anchor_profile_fee', '100')");
    echo "Default anchor_profile_fee setting inserted.<br>";
}

echo json_encode(["status" => "success", "message" => "Settings table migration checked and applied."]);
?>
