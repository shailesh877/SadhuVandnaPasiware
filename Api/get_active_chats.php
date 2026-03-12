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

    if ($platform === 'marriage') {
        // Join with marriage profiles
        $p = $con->query("SELECT full_name, photo FROM tbl_marriage_profiles WHERE id = $partner_id LIMIT 1")->fetch_assoc();
        if ($p) {
            $partner_name = $p['full_name'];
            $partner_photo = $p['photo'];
        }
    } else {
        // Join with members
        $p = $con->query("SELECT name, profile_photo FROM tbl_members WHERE id = $partner_id LIMIT 1")->fetch_assoc();
        if ($p) {
            $partner_name = $p['name'];
            $partner_photo = $p['profile_photo'];
        }
    }

    $chats[] = [
        "partner_id" => $partner_id,
        "full_name" => $partner_name,
        "profile_photo" => $partner_photo,
        "last_message" => $row['message'],
        "time" => date("h:i A", strtotime($row['created_at'])),
        "unread" => ($row['receiver_id'] == $search_id && $row['seen'] == 0) ? 1 : 0
    ];
}

echo json_encode(["status" => "success", "data" => $chats]);
?>
