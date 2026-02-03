<?php
include("connection.php");
include("headers.php");

$user_id = intval($_GET['user_id'] ?? 0); // This is the App User ID (tbl_users/members id)

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

// 1. Get Marriage Profile ID for this user
$mpQuery = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1");
if ($mpQuery->num_rows == 0) {
    echo json_encode(["status" => "success", "data" => []]); // No profile, no chats
    exit;
}
$my_profile_id = $mpQuery->fetch_assoc()['id'];

// 2. Fetch conversions
// We need unique partners from tbl_messages
// Logic: Find all message IDs involving me, then group by the OTHER party.
// To get the latest message content, we can use a subquery or strict grouping.

$sql = "
    SELECT 
        CASE 
            WHEN m.sender_id = $my_profile_id THEN m.receiver_id 
            ELSE m.sender_id 
        END AS partner_id,
        MAX(m.created_at) as last_msg_time,
        mp.full_name,
        mp.photo as profile_photo,
        mp.city,
        (SELECT message FROM tbl_messages WHERE (sender_id = partner_id AND receiver_id = $my_profile_id) OR (sender_id = $my_profile_id AND receiver_id = partner_id) ORDER BY created_at DESC LIMIT 1) as last_message
    FROM tbl_messages m
    JOIN tbl_marriage_profiles mp ON (
        (m.sender_id = $my_profile_id AND mp.id = m.receiver_id) OR
        (m.sender_id = mp.id AND m.receiver_id = $my_profile_id)
    )
    WHERE m.sender_id = $my_profile_id OR m.receiver_id = $my_profile_id
    GROUP BY partner_id
    ORDER BY last_msg_time DESC
";

$res = $con->query($sql);
$chats = [];

while ($row = $res->fetch_assoc()) {
    $chats[] = [
        "partner_id" => $row['partner_id'],
        "full_name" => $row['full_name'],
        "profile_photo" => $row['profile_photo'],
        "city" => $row['city'],
        "last_message" => $row['last_message'],
        "time" => date("h:i A", strtotime($row['last_msg_time'])),
        "date_full" => $row['last_msg_time'] // for sorting if needed
    ];
}

echo json_encode(["status" => "success", "data" => $chats]);
?>
