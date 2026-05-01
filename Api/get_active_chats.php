<?php
// get_active_chats.php
include 'headers.php';
include 'connection.php';

$user_id = intval($_GET['user_id'] ?? 0);
$platform = $_GET['platform'] ?? 'marriage'; // marriage or community

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Invalid User ID"]);
    exit;
}

// 1. Get the profile/member ID to search in tbl_messages
$search_id = 0;
if ($platform === 'marriage') {
    $mp = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1")->fetch_assoc();
    $search_id = $mp['id'] ?? 0;
} else {
    $search_id = $user_id; // Community uses member_id directly
}

if (!$search_id) {
    echo json_encode(["status" => "success", "data" => []]);
    exit;
}

// 2. Query to find unique partners from tbl_messages
// We use a subquery to get the latest message for each partner
$sql = "
    SELECT 
        m1.*, 
        CASE 
            WHEN m1.sender_id = $search_id THEN m1.receiver_id 
            ELSE m1.sender_id 
        END AS partner_id
    FROM tbl_messages m1
    INNER JOIN (
        SELECT 
            MAX(id) as max_id
        FROM tbl_messages
        WHERE (sender_id = $search_id OR receiver_id = $search_id)
        AND chat_platform = '$platform'
        GROUP BY 
            CASE 
                WHEN sender_id = $search_id THEN receiver_id 
                ELSE sender_id 
            END
    ) m2 ON m1.id = m2.max_id
    ORDER BY m1.created_at DESC
";

$res = $con->query($sql);
$chats = [];

while ($row = $res->fetch_assoc()) {
    $partner_id = $row['partner_id'];
    $partner_name = "User";
    $partner_photo = "";

    $is_online = false;
    if ($platform === 'marriage') {
        // Join with marriage profiles and members to get online status
        $p = $con->query("SELECT mp.full_name, mp.photo, (m.last_active >= NOW() - INTERVAL 5 MINUTE) as is_online 
                          FROM tbl_marriage_profiles mp 
                          JOIN tbl_members m ON mp.user_id = m.id
                          WHERE mp.id = $partner_id LIMIT 1")->fetch_assoc();
        if ($p) {
            $partner_name = $p['full_name'];
            $partner_photo = $p['photo'];
            $is_online = ($p['is_online'] == 1);
        }
    } else {
        // Join with members
        $p = $con->query("SELECT name, profile_photo, (last_active >= NOW() - INTERVAL 5 MINUTE) as is_online 
                          FROM tbl_members WHERE id = $partner_id LIMIT 1")->fetch_assoc();
        if ($p) {
            $partner_name = $p['name'];
            $partner_photo = $p['profile_photo'];
            $is_online = ($p['is_online'] == 1);
        }
    }

    $chats[] = [
        "partner_id" => $partner_id,
        "full_name" => $partner_name,
        "profile_photo" => $partner_photo,
        "last_message" => $row['message'],
        "time" => date("h:i A", strtotime($row['created_at'])),
        "timestamp" => $row['created_at'],
        "unread" => ($row['receiver_id'] == $search_id && $row['seen'] == 0) ? 1 : 0,
        "is_online" => $is_online
    ];
}

// 3. Fetch groups the user is a part of
$groups_sql = "
    SELECT g.id, g.name, g.photo, g.created_by,
           (SELECT message FROM tbl_group_messages WHERE group_id = g.id ORDER BY created_at DESC LIMIT 1) as last_message,
           (SELECT created_at FROM tbl_group_messages WHERE group_id = g.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
           g.created_at as group_created_at
    FROM tbl_groups g
    INNER JOIN tbl_group_members gm ON g.id = gm.group_id
    WHERE gm.user_id = $search_id AND g.platform = '$platform'
";
$groups_res = $con->query($groups_sql);
if ($groups_res) {
    while ($grow = $groups_res->fetch_assoc()) {
        $msg_time = $grow['last_message_time'] ? $grow['last_message_time'] : $grow['group_created_at'];
        $chats[] = [
            "partner_id" => $grow['id'], // use group_id as partner_id for mapping
            "full_name" => $grow['name'],
            "profile_photo" => $grow['photo'],
            "last_message" => $grow['last_message'] ?: 'Group created',
            "time" => date("h:i A", strtotime($msg_time)),
            "timestamp" => $msg_time,
            "unread" => 0, // Simplified for now
            "isGroup" => true, // Flag to identify group chats
            "created_by" => $grow['created_by'],
            "is_online" => false // Groups don't have online status
        ];
    }
}

// Sort chats by timestamp (most recent first)
usort($chats, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

echo json_encode(["status" => "success", "data" => $chats]);
?>
