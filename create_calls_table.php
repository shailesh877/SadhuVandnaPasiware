<?php
include("connection.php");

$sql = "CREATE TABLE IF NOT EXISTS tbl_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caller_id INT NOT NULL,
    receiver_id INT NOT NULL,
    type VARCHAR(10) NOT NULL DEFAULT 'video',
    status VARCHAR(20) NOT NULL DEFAULT 'ringing', 
    caller_peer_id VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($con->query($sql) === TRUE) {
    echo "Table tbl_calls created successfully";
} else {
    echo "Error creating table: " . $con->error;
}
?>
