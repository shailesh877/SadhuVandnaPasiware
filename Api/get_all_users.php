<?php
include 'headers.php';
include 'connection.php';

$user_id = intval($_GET['user_id'] ?? 0);
$only_connected = isset($_GET['only_connected']) ? intval($_GET['only_connected']) : 1;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Invalid User ID"]);
    exit;
}

if ($only_connected) {
    // Only return users who are connected (accepted followers/following) with $user_id
    $sql = "SELECT DISTINCT m.id as partner_id, m.name as full_name, m.profile_photo, (m.last_active >= NOW() - INTERVAL 5 MINUTE) as is_online 
            FROM tbl_members m 
            JOIN tbl_followers f ON (
                (m.id = f.follower_id AND f.following_id = $user_id AND f.status = 'accepted')
                OR 
                (m.id = f.following_id AND f.follower_id = $user_id AND f.status = 'accepted')
            )
            WHERE m.status != 'Blocked' AND m.id != $user_id 
            ORDER BY m.name ASC";
} else {
    $sql = "SELECT id as partner_id, name as full_name, profile_photo, (last_active >= NOW() - INTERVAL 5 MINUTE) as is_online 
            FROM tbl_members 
            WHERE status != 'Blocked' AND id != $user_id 
            ORDER BY name ASC";
}

$res = $con->query($sql);
$users = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $users[] = [
            "partner_id" => intval($row['partner_id']),
            "full_name" => $row['full_name'] ?: 'Unknown User',
            "profile_photo" => $row['profile_photo'] ?: '',
            "isGroup" => false,
            "is_online" => ($row['is_online'] == 1)
        ];
    }
}

echo json_encode(["status" => "success", "data" => $users]);
?>
