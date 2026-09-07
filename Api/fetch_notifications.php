<?php
include("connection.php");
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

// Handle JSON Input
$json = file_get_contents('php://input');
$data_input = json_decode($json, true);

$user_id = intval($_REQUEST['user_id'] ?? $data_input['user_id'] ?? 0);
$action = $_REQUEST['action'] ?? '';

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User ID required']);
    exit;
}

// Get Marriage Profile ID for Chat/Requests
$mp = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1");
$my_marriage_id = ($mp && $mp->num_rows > 0) ? $mp->fetch_assoc()['id'] : 0;

if ($action === 'count') {
    // Rely strictly on tbl_notifications for the bell icon badge count.
    $systemQ = $con->query("SELECT COUNT(*) FROM tbl_notifications WHERE user_id = '$user_id' AND seen = 0");
    $total = $systemQ ? (int)$systemQ->fetch_row()[0] : 0;
    
    echo json_encode(['status' => 'success', 'unread_count' => $total]);
    exit;
}

$notifications = [];

// 1. Messages (Marriage)
$q1 = $con->query("
    SELECT 'message' as n_type, m1.id, m1.sender_id, m1.chat_platform as platform, m1.message, m1.created_at, mp.full_name as sender_name, mp.photo as sender_photo, 
    (SELECT COUNT(*) FROM tbl_messages WHERE sender_id = m1.sender_id AND receiver_id = m1.receiver_id AND chat_platform = 'marriage' AND seen = 0) as unread_count, '' as data_payload
    FROM tbl_messages m1 
    INNER JOIN (SELECT sender_id, MAX(id) as last_id FROM tbl_messages WHERE receiver_id = '$my_marriage_id' AND chat_platform = 'marriage' AND seen = 0 GROUP BY sender_id) m2 ON m1.id = m2.last_id 
    LEFT JOIN tbl_marriage_profiles mp ON m1.sender_id = mp.id
");
if ($q1) while ($row = $q1->fetch_assoc()) $notifications[] = $row;

// 2. Messages (Community)
$q2 = $con->query("
    SELECT 'message' as n_type, m1.id, m1.sender_id, m1.chat_platform as platform, m1.message, m1.created_at, mem.name as sender_name, mem.profile_photo as sender_photo, 
    (SELECT COUNT(*) FROM tbl_messages WHERE sender_id = m1.sender_id AND receiver_id = m1.receiver_id AND chat_platform = 'community' AND seen = 0) as unread_count, '' as data_payload
    FROM tbl_messages m1 
    INNER JOIN (SELECT sender_id, MAX(id) as last_id FROM tbl_messages WHERE receiver_id = '$user_id' AND chat_platform = 'community' AND seen = 0 GROUP BY sender_id) m3 ON m1.id = m3.last_id 
    LEFT JOIN tbl_members mem ON m1.sender_id = mem.id
");
if ($q2) while ($row = $q2->fetch_assoc()) $notifications[] = $row;

// 3. Proposals
$q3 = $con->query("
    SELECT 'proposal' as n_type, p.id, p.sender_id, 'marriage' as platform, 'Sent you a marriage proposal' as message, p.created_at, mp.full_name as sender_name, mp.photo as sender_photo, 1 as unread_count, '' as data_payload
    FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.sender_id = mp.id WHERE p.receiver_id = '$my_marriage_id' AND p.status = 'pending'
");
if ($q3) while ($row = $q3->fetch_assoc()) $notifications[] = $row;

// 4. Follows
$q4 = $con->query("
    SELECT 'follow' as n_type, f.id, f.follower_id as sender_id, 'community' as platform, 'Sent you a friend request' as message, f.created_at, mem.name as sender_name, mem.profile_photo as sender_photo, 1 as unread_count, '' as data_payload
    FROM tbl_followers f JOIN tbl_members mem ON f.follower_id = mem.id WHERE f.following_id = '$user_id' AND f.status = 'pending'
");
if ($q4) while ($row = $q4->fetch_assoc()) $notifications[] = $row;

// 5. News
$q5 = $con->query("
    SELECT 'news' as n_type, n.id, 0 as sender_id, 'news' as platform, n.title as message, n.created_at, 'Samaj News' as sender_name, 'images/logo.png' as sender_photo, 0 as unread_count, '' as data_payload
    FROM tbl_news n ORDER BY created_at DESC LIMIT 10
");
if ($q5) while ($row = $q5->fetch_assoc()) $notifications[] = $row;

// 6. System Notifications
$q6 = $con->query("
    SELECT IFNULL(h.type, 'system') as n_type, h.id, 0 as sender_id, 'system' as platform, h.message, h.created_at, h.title as sender_name, 'images/logo.png' as sender_photo, (CASE WHEN h.seen = 0 THEN 1 ELSE 0 END) as unread_count, IFNULL(h.data_payload, '') as data_payload
    FROM tbl_notifications h WHERE h.user_id = '$user_id' ORDER BY created_at DESC LIMIT 20
");
if ($q6) while ($row = $q6->fetch_assoc()) $notifications[] = $row;

// Sort combined results by created_at DESC
usort($notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

$formattedItems = [];

foreach ($notifications as $row) {
    $photo = $row['sender_photo'];
    $n_type = $row['n_type'];
    $platform = $row['platform'];
    
    $img = "images/logo.png";
    if($platform === 'community'){
        $img = $photo ? 'uploads/photo/'.$photo : 'https://via.placeholder.com/150';
    } elseif($platform === 'marriage') {
        $img = $photo ? ( (strpos($photo,'http')===0) ? $photo : 'uploads/photo/'.$photo ) : 'images/logo.png';
    }

    $extra_data = [];
    if (!empty($row['data_payload'])) {
        $extra_data = json_decode($row['data_payload'], true) ?? [];
    }

    $formattedItems[] = [
        'type'          => $n_type,
        'id'            => $platform . '_' . $n_type . '_' . $row['id'],
        'sender_id'     => $row['sender_id'],
        'title'         => $row['sender_name'] ?? 'User',
        'body'          => $row['message'],
        'image'         => $img,
        'date'          => $row['created_at'],
        'unread_count'  => $row['unread_count'],
        'platform'      => $platform,
        'data'          => array_merge([
            'sender_id' => $row['sender_id'],
            'news_id'   => ($n_type == 'news') ? $row['id'] : null
        ], $extra_data)
    ];
}

echo json_encode(['status' => 'success', 'data' => $formattedItems]);
?>
