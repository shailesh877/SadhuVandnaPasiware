<?php
include("connection.php");

$sql = "CREATE TABLE IF NOT EXISTS tbl_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'system',
    data_payload TEXT,
    seen TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (seen)
)";

if ($con->query($sql) === TRUE) {
    echo "<h1>Success!</h1><p>Table 'tbl_notifications' created successfully.</p>";
    echo "<p>Ab aap is file ko delete kar sakte hain.</p>";
} else {
    echo "<h1>Error!</h1><p>Error creating table: " . $con->error . "</p>";
}
?>
