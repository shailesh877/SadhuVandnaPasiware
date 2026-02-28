<?php
// $con=mysqli_connect("localhost","root","","sadhu_vandana");
// if (mysqli_connect_errno()) {
//     echo "Failed to connect to MySQL: " . mysqli_connect_error();
//     exit();
// }
// $con->set_charset("utf8mb4");
// $con->query("SET NAMES 'utf8mb4'"); 
// $con->query("SET CHARACTER SET utf8mb4");
// header('Content-Type: text/html; charset=utf-8');
?>

<?php

$con=mysqli_connect("e4skgkwwk0s0gkso48oc40kg","u941015828_sadhuvandna","Sadhuvandna7832%^","u941015828_sadhuvandna",3306);
mysqli_set_charset($con, "utf8mb4");

mysqli_query($con, "SET NAMES utf8mb4");
mysqli_query($con, "SET CHARACTER SET utf8mb4");
mysqli_query($con, "SET SESSION collation_connection = utf8mb4_unicode_ci");
// include "database_schema_updates.php";
?>

<?php
// Database Auto-migration / Schema Sync
// This script ensures all required tables and columns exist automatically.

$queries = [
    // 1. Table for Blocked Users
    "CREATE TABLE IF NOT EXISTS tbl_blocked_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        blocker_id INT NOT NULL,
        blocked_id INT NOT NULL,
        chat_platform ENUM('marriage', 'community') DEFAULT 'marriage',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_block_platform (blocker_id, blocked_id, chat_platform)
    )",

    // 2. Table for Audio/Video Calls
    "CREATE TABLE IF NOT EXISTS tbl_calls (
        id INT AUTO_INCREMENT PRIMARY KEY,
        caller_id INT NOT NULL,
        receiver_id INT NOT NULL,
        type VARCHAR(10) NOT NULL DEFAULT 'video',
        status VARCHAR(20) NOT NULL DEFAULT 'ringing', 
        caller_peer_id VARCHAR(100) NOT NULL,
        chat_platform ENUM('marriage', 'community') DEFAULT 'marriage',
        duration INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    // 3. Table for Friend Requests / Followers
    "CREATE TABLE IF NOT EXISTS tbl_followers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        status ENUM('pending', 'accepted') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY follower_following (follower_id, following_id)
    )",

    // 4. Table for Real-time Typing Indicators
    "CREATE TABLE IF NOT EXISTS tbl_typing (
        id INT AUTO_INCREMENT PRIMARY KEY,
        profile_id INT NOT NULL,
        target_profile_id INT NOT NULL,
        is_typing TINYINT(1) DEFAULT 0,
        chat_platform ENUM('marriage', 'community') DEFAULT 'marriage',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY ux_pair_platform (profile_id, target_profile_id, chat_platform)
    )"
];

foreach ($queries as $sql) {
    if (!mysqli_query($con, $sql)) {
        // Log error but don't stop the app
        error_log("Migration Error (Table): " . mysqli_error($con));
    }
}

// 5. Adding Platform Support to Messages (ALTER handles differently)
$cols = [
    "is_community" => "ALTER TABLE tbl_messages ADD COLUMN is_community TINYINT(1) DEFAULT 0",
    "chat_platform" => "ALTER TABLE tbl_messages ADD COLUMN chat_platform ENUM('marriage', 'community') DEFAULT 'marriage'"
];

foreach ($cols as $col => $alter_sql) {
    $check = mysqli_query($con, "SHOW COLUMNS FROM `tbl_messages` LIKE '$col'");
    if (mysqli_num_rows($check) == 0) {
        if (!mysqli_query($con, $alter_sql)) {
            error_log("Migration Error (Column $col): " . mysqli_error($con));
        }
    }
}
?>

