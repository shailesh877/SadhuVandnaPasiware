<?php
include 'headers.php';
include 'connection.php';

// Create tbl_groups
$sql1 = "CREATE TABLE IF NOT EXISTS `tbl_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `platform` varchar(50) DEFAULT 'community',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

// Create tbl_group_members
$sql2 = "CREATE TABLE IF NOT EXISTS `tbl_group_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

// Create tbl_group_messages
$sql3 = "CREATE TABLE IF NOT EXISTS `tbl_group_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `seen_by` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `sender_id` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

$success = true;

if (!$con->query($sql1)) {
    echo "Error creating tbl_groups: " . $con->error . "<br>";
    $success = false;
}
if (!$con->query($sql2)) {
    echo "Error creating tbl_group_members: " . $con->error . "<br>";
    $success = false;
}
if (!$con->query($sql3)) {
    echo "Error creating tbl_group_messages: " . $con->error . "<br>";
    $success = false;
}

if ($success) {
    echo json_encode(["status" => "success", "message" => "Group tables created successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to create some tables."]);
}
?>
