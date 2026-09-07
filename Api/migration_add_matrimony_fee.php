<?php
include 'headers.php';
include 'connection.php';

// Disable strict mode for this session to avoid issues with zero dates
$con->query("SET SESSION sql_mode = ''");

// 1. Add matrimony_profile_fee to tbl_members if it doesn't exist
$check_fee = $con->query("SHOW COLUMNS FROM `tbl_members` LIKE 'matrimony_profile_fee'");
if ($check_fee && $check_fee->num_rows == 0) {
    $con->query("ALTER TABLE `tbl_members` ADD COLUMN `matrimony_profile_fee` INT DEFAULT 500");
    WriteLog("Added matrimony_profile_fee to tbl_members");
}

// 2. Add payment_type to tbl_wallet if it doesn't exist
$check_wallet = $con->query("SHOW COLUMNS FROM `tbl_wallet` LIKE 'payment_type'");
if ($check_wallet && $check_wallet->num_rows == 0) {
    $con->query("ALTER TABLE `tbl_wallet` ADD COLUMN `payment_type` VARCHAR(50) DEFAULT 'chat'");
    WriteLog("Added payment_type to tbl_wallet");
}

function WriteLog($msg) {
    echo $msg . "<br>";
}

echo json_encode(["status" => "success", "message" => "Database migrations checked and applied."]);
?>
