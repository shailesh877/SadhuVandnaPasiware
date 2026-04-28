<?php
include 'headers.php';
include 'connection.php';

$invite_code = $con->real_escape_string($_GET['invite_code'] ?? '');

if (!$invite_code) {
    echo json_encode(["status" => "error", "message" => "Invite code is required"]);
    exit;
}

$res = $con->query("SELECT id, name, photo, description, platform FROM tbl_groups WHERE invite_code = '$invite_code'");
$group = $res->fetch_assoc();

if (!$group) {
    echo json_encode(["status" => "error", "message" => "Invalid invite code"]);
    exit;
}

echo json_encode(["status" => "success", "data" => $group]);
?>
