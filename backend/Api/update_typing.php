<?php
// update_typing.php
include("connection.php");
session_start();
date_default_timezone_set('Asia/Kolkata');

$profile_id = intval($_POST['profile_id'] ?? 0);
$target = intval($_POST['target_profile_id'] ?? 0);
$is = intval($_POST['is_typing'] ?? 0);
if(!$profile_id || !$target){ echo "error"; exit; }

// insert or update
$stmt = $con->prepare("INSERT INTO tbl_typing (profile_id, target_profile_id, is_typing, updated_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE is_typing=VALUES(is_typing), updated_at=NOW()");
$stmt->bind_param("iii", $profile_id, $target, $is);
$stmt->execute();
echo "ok";
