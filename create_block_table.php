<?php
include("connection.php");

$sql = "CREATE TABLE IF NOT EXISTS tbl_blocked_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blocker_id INT NOT NULL,
    blocked_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_block (blocker_id, blocked_id)
)";

if ($con->query($sql) === TRUE) {
    echo "Table tbl_blocked_users created successfully";
} else {
    echo "Error creating table: " . $con->error;
}
?>
