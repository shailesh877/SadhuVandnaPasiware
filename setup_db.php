<?php
include("connection.php");

$sql = "CREATE TABLE IF NOT EXISTS music (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    artist VARCHAR(255),
    file_name VARCHAR(255) NOT NULL,
    tags TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($con, $sql)) {
    echo "Success: Table 'music' created successfully or already exists.";
} else {
    echo "Error: " . mysqli_error($con);
}
?>
