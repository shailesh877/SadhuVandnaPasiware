<?php
include 'headers.php';
include 'connection.php';

echo "<h3>Database Setup Script</h3>";

// 1. Update tbl_members
$sql_add_cols = "ALTER TABLE tbl_members 
    ADD COLUMN IF NOT EXISTS is_business TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS parent_userid INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS category VARCHAR(100) DEFAULT NULL";

if ($con->query($sql_add_cols) === TRUE) {
    echo "<p>Success: tbl_members updated successfully.</p>";
} else {
    echo "<p>Error updating tbl_members: " . $con->error . "</p>";
}

// 2. Create tbl_business_details
$sql_create_table = "CREATE TABLE IF NOT EXISTS tbl_business_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone_no VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    website_url VARCHAR(255) DEFAULT NULL,
    social_media_account VARCHAR(255) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    district VARCHAR(100) DEFAULT NULL,
    full_address TEXT DEFAULT NULL,
    pincode VARCHAR(20) DEFAULT NULL,
    opening_hours VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tbl_members(id) ON DELETE CASCADE
)";

if ($con->query($sql_create_table) === TRUE) {
    echo "<p>Success: tbl_business_details table created successfully.</p>";
} else {
    echo "<p>Error creating tbl_business_details: " . $con->error . "</p>";
}

echo "<p>Setup Complete!</p>";
?>
