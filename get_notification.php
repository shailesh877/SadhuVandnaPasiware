<?php
include("connection.php");
session_start();

$user_mobile = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_mobile){ echo json_encode([]); exit; }

// Get my IDs
$userQ = $con->query("SELECT id FROM tbl_members WHERE mobile='$user_mobile' LIMIT 1");
if($userQ->num_rows == 0){ echo json_encode([]); exit; }
$user_id = $userQ->fetch_assoc()['id'];

$mp = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1");
$my_marriage_id = ($mp->num_rows > 0) ? $mp->fetch_assoc()['id'] : 0;

/*
  UNION QUERY to get:
  1. Latest unread message per sender+platform
  2. Pending Marriage Proposals
  3. Pending Community Follow Requests
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
    ORDER BY created_at DESC
";

$q = $con->query($sql);
$data = [];

while($row = $q->fetch_assoc()){
    $photo = $row['sender_photo'];
    if($row['platform'] === 'community'){
        $img = $photo ? 'uploads/photo/'.$photo : 'https://via.placeholder.com/150';
    } else {
        $img = $photo ? ( (strpos($photo,'http')===0) ? $photo : 'uploads/photo/'.$photo ) : 'images/logo.png';
    }

    $name_prefix = "";
    if($row['n_type'] == 'message') $name_prefix = $row['platform'] == 'community' ? ' (Community)' : ' (Marriage)';
    
    $msg_text = $row['message'];
    if (strpos($msg_text, 'SYSTEM_CALL:') === 0) {
        $msg_text = substr($msg_text, 12);
    }
    
    $data[] = [
        'type'          => $row['n_type'],
        'id'            => $row['id'],
        'sender_id'     => $row['sender_id'],
        'name'          => ($row['sender_name'] ?? 'User') . $name_prefix,
        'profile'       => $img,
        'message'       => $msg_text,
        'date'          => date("d M h:i A", strtotime($row['created_at'])),
        'unread_count'  => $row['unread_count'],
        'platform'      => $row['platform']
    ];
}

echo json_encode($data);
?>
