<?php
include 'headers.php';
include 'connection.php';

$group_id = intval($_GET['group_id'] ?? 0);

if (!$group_id) {
    echo json_encode(["status" => "error", "message" => "Group ID is required"]);
    exit;
}

// Auto-migration check: add admins_only if it doesn't exist
$check_adm = $con->query("SHOW COLUMNS FROM `tbl_groups` LIKE 'admins_only'");
if ($check_adm->num_rows == 0) {
    $con->query("ALTER TABLE `tbl_groups` ADD COLUMN `admins_only` TINYINT(1) DEFAULT 0");
}

// Auto-migration check: add description if it doesn't exist
$check_desc = $con->query("SHOW COLUMNS FROM `tbl_groups` LIKE 'description'");
if ($check_desc->num_rows == 0) {
    $con->query("ALTER TABLE `tbl_groups` ADD COLUMN `description` TEXT DEFAULT NULL");
}

$res = $con->query("SELECT invite_code, admins_only, description FROM tbl_groups WHERE id = $group_id");
if (!$res) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $con->error]);
    exit;
}
$row = $res->fetch_assoc();

if (!$row) {
    echo json_encode(["status" => "error", "message" => "Group not found"]);
    exit;
}

$invite_code = $row['invite_code'];
$admins_only = intval($row['admins_only']);

if (!$invite_code) {
    // Generate a unique 8-character code
    $invite_code = bin2hex(random_bytes(4)); 
    $con->query("UPDATE tbl_groups SET invite_code = '$invite_code' WHERE id = $group_id");
}

echo json_encode([
    "status" => "success", 
    "invite_code" => $invite_code, 
    "admins_only" => $admins_only,
    "description" => $row['description'] ?? ''
]);
?>
