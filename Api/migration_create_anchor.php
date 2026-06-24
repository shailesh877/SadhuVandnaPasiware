<?php
include 'headers.php';
include 'connection.php';

// Disable strict mode for this session
$con->query("SET SESSION sql_mode = ''");

// Create tbl_anchor_applications table if it doesn't exist
$con->query("
    CREATE TABLE IF NOT EXISTS `tbl_anchor_applications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNIQUE NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(50) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `education` VARCHAR(255) NOT NULL,
        `photo` VARCHAR(255) NOT NULL,
        `aadhaar` VARCHAR(255) NOT NULL,
        `resume` VARCHAR(255) NOT NULL,
        `status` VARCHAR(50) DEFAULT 'Pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

echo json_encode(["status" => "success", "message" => "Anchor applications table migration checked and applied."]);
?>
