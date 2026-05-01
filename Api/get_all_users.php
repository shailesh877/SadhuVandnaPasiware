<?php
include 'headers.php';
include 'connection.php';

$user_id = intval($_GET['user_id'] ?? 0);

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Invalid User ID"]);
    exit;
}

$sql = "SELECT id as partner_id, name as full_name, profile_photo FROM tbl_members WHERE status != 'Blocked' AND id != $user_id ORDER BY name ASC LIMIT 500";
$res = $con->query($sql);
$users = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $users[] = [
            "partner_id" => intval($row['partner_id']),
            "full_name" => $row['full_name'] ?: 'Unknown User',
            "profile_photo" => $row['profile_photo'] ?: '',
            "isGroup" => false
        ];
    }
}

echo json_encode(["status" => "success", "data" => $users]);
?>
