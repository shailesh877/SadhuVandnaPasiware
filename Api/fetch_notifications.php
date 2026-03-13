<?php
include("connection.php");
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User ID required']);
    exit;
}

// Get Marriage Profile ID for Chat/Requests
$mp = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1");
$my_marriage_id = ($mp && $mp->num_rows > 0) ? $mp->fetch_assoc()['id'] : 0;

/*
  UNION QUERY to aggregate all notification types (Website Aligned):
  1. Unread Messages (Marriage)
  2. Unread Messages (Community)
  3. Pending Marriage Proposals
  4. Pending Follow Requests
  5. Samaj News (Global)
  6. System History (Logged pushes)
*/

$sql = "
    (
        -- Messages (Marriage)
        SELECT 
            'message' as n_type, m1.id, m1.sender_id, m1.chat_platform as platform, 
            m1.message, m1.created_at, mp.full_name as sender_name, mp.photo as sender_photo,
            (SELECT COUNT(*) FROM tbl_messages WHERE sender_id = m1.sender_id AND receiver_id = m1.receiver_id AND chat_platform = 'marriage' AND seen = 0) as unread_count
        FROM tbl_messages m1
        INNER JOIN (
            SELECT sender_id, MAX(id) as last_id 
            FROM tbl_messages 
            WHERE receiver_id = '$my_marriage_id' AND chat_platform = 'marriage' AND seen = 0
            GROUP BY sender_id
        ) m2 ON m1.id = m2.last_id
        LEFT JOIN tbl_marriage_profiles mp ON m1.sender_id = mp.id
    )
    UNION ALL
    (
        -- Messages (Community)
        SELECT 
            'message' as n_type, m1.id, m1.sender_id, m1.chat_platform as platform, 
            m1.message, m1.created_at, mem.name as sender_name, mem.profile_photo as sender_photo,
            (SELECT COUNT(*) FROM tbl_messages WHERE sender_id = m1.sender_id AND receiver_id = m1.receiver_id AND chat_platform = 'community' AND seen = 0) as unread_count
        FROM tbl_messages m1
        INNER JOIN (
            SELECT sender_id, MAX(id) as last_id 
            FROM tbl_messages 
            WHERE receiver_id = '$user_id' AND chat_platform = 'community' AND seen = 0
            GROUP BY sender_id
        ) m3 ON m1.id = m3.last_id
        LEFT JOIN tbl_members mem ON m1.sender_id = mem.id
    )
    UNION ALL
    (
        -- Proposals (Marriage)
        SELECT 
            'proposal' as n_type, p.id, p.sender_id, 'marriage' as platform,
            'Sent you a marriage proposal' as message, p.created_at, mp.full_name as sender_name, mp.photo as sender_photo,
            1 as unread_count
        FROM tbl_proposals p
        JOIN tbl_marriage_profiles mp ON p.sender_id = mp.id
        WHERE p.receiver_id = '$my_marriage_id' AND p.status = 'pending'
    )
    UNION ALL
    (
        -- Follows (Community)
        SELECT 
            'follow' as n_type, f.id, f.follower_id as sender_id, 'community' as platform,
            'Sent you a friend request' as message, f.created_at, mem.name as sender_name, mem.profile_photo as sender_photo,
            1 as unread_count
        FROM tbl_followers f
        JOIN tbl_members mem ON f.follower_id = mem.id
        WHERE f.following_id = '$user_id' AND f.status = 'pending'
    )
    UNION ALL
    (
        -- News (Global)
        SELECT 
            'news' as n_type, n.id, 0 as sender_id, 'news' as platform,
            n.title as message, n.created_at, 'Samaj News' as sender_name, 'images/logo.png' as sender_photo,
            0 as unread_count
        FROM tbl_news n
        ORDER BY id DESC LIMIT 10
    )
    UNION ALL
    (
        -- System (Logged Pushes)
        SELECT 
            'system' as n_type, h.id, 0 as sender_id, 'system' as platform,
            h.message, h.created_at, h.title as sender_name, 'images/logo.png' as sender_photo,
            (CASE WHEN h.seen = 0 THEN 1 ELSE 0 END) as unread_count
        FROM tbl_notifications h
        WHERE h.user_id = '$user_id'
        ORDER BY id DESC LIMIT 15
    )
    ORDER BY created_at DESC
";

$q = $con->query($sql);
$data = [];

if ($q) {
    while($row = $q->fetch_assoc()){
        $photo = $row['sender_photo'];
        $n_type = $row['n_type'];
        
        $img = "images/logo.png";
        if($row['platform'] === 'community'){
            $img = $photo ? 'uploads/photo/'.$photo : 'https://via.placeholder.com/150';
        } elseif($row['platform'] === 'marriage') {
            $img = $photo ? ( (strpos($photo,'http')===0) ? $photo : 'uploads/photo/'.$photo ) : 'images/logo.png';
        }

        $data[] = [
            'type'          => $n_type,
            'id'            => $row['n_type'] . '_' . $row['id'],
            'sender_id'     => $row['sender_id'],
            'title'         => $row['sender_name'] ?? 'User',
            'body'          => $row['message'],
            'image'         => $img,
            'date'          => $row['created_at'],
            'unread_count'  => $row['unread_count'],
            'platform'      => $row['platform'],
            'data'          => [
                'sender_id' => $row['sender_id'],
                'news_id'   => ($n_type == 'news') ? $row['id'] : null
            ]
        ];
    }
}

// Sort all by date DESC
usort($notifications, function ($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

echo json_encode(['status' => 'success', 'data' => $notifications]);
?>
