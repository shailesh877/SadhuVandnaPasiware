<?php
include("connection.php");
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? 0;

if(!$user_id){
    echo json_encode(['status' => 'error', 'message' => 'User ID required']);
    exit;
}

// Get Marriage Profile ID for Chat/Requests
$mp = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1");
$profile_id = 0;
if($mp->num_rows > 0){
    $profile_id = $mp->fetch_assoc()['id'];
}

$notifications = [];

// 1. Unread Messages (Source: tbl_messages, Key: receiver_id = profile_id)
if($profile_id){
    // Get latest unread message per sender
    $msgQ = $con->query("
        SELECT m.sender_id, m.message, m.created_at, p.full_name as name, p.photo
        FROM tbl_messages m
        JOIN tbl_marriage_profiles p ON m.sender_id = p.id
        WHERE m.receiver_id = '$profile_id' AND m.seen = 0
        GROUP BY m.sender_id
        ORDER BY m.created_at DESC
    ");
    
    while($r = $msgQ->fetch_assoc()){
        $notifications[] = [
            'type' => 'message',
            'id' => 'msg_'.$r['sender_id'],
            'title' => $r['name'],
            'body' => 'Sent you a message: ' . substr($r['message'], 0, 30) . '...',
            'date' => $r['created_at'],
            'image' => $r['photo'],
            'data' => ['sender_id' => $r['sender_id']]
        ];
    }

    // 2. Pending Requests (Source: tbl_proposals, Key: receiver_id = profile_id)
    $reqQ = $con->query("
        SELECT p.id, p.sender_id, p.created_at, mp.full_name as name, mp.photo
        FROM tbl_proposals p
        JOIN tbl_marriage_profiles mp ON p.sender_id = mp.id
        WHERE p.receiver_id = '$profile_id' AND p.status = 'pending'
    ");

    while($r = $reqQ->fetch_assoc()){
        $notifications[] = [
            'type' => 'request',
            'id' => 'req_'.$r['id'],
            'title' => $r['name'],
            'body' => 'Sent you a connection request',
            'date' => $r['created_at'],
            'image' => $r['photo'],
            'data' => ['request_id' => $r['id']]
        ];
    }
}

// 3. Likes (Source: tbl_likes, Key: post_id -> user_id)
$likeQ = $con->query("
    SELECT l.id, l.user_id, l.date, m.name, m.profile_photo, l.post_id
    FROM tbl_likes l
    JOIN tbl_members m ON l.user_id = m.id
    WHERE l.post_id IN (SELECT id FROM tbl_posts WHERE user_id = '$user_id')
    AND l.user_id != '$user_id'
    ORDER BY l.date DESC LIMIT 20
");

while($r = $likeQ->fetch_assoc()){
    $notifications[] = [
        'type' => 'like',
        'id' => 'like_'.$r['id'],
        'title' => $r['name'],
        'body' => 'Liked your post',
        'date' => $r['date'],
        'image' => $r['profile_photo'],
        'data' => ['post_id' => $r['post_id'], 'user_id' => $r['user_id']]
    ];
}

// 4. Comments (Source: tbl_comments, Key: post_id -> user_id)
$commQ = $con->query("
    SELECT c.id, c.user_id, c.date, m.name, m.profile_photo, c.post_id, c.comment
    FROM tbl_comments c
    JOIN tbl_members m ON c.user_id = m.id
    WHERE c.post_id IN (SELECT id FROM tbl_posts WHERE user_id = '$user_id')
    AND c.user_id != '$user_id'
    ORDER BY c.date DESC LIMIT 20
");

while($r = $commQ->fetch_assoc()){
    $notifications[] = [
        'type' => 'comment',
        'id' => 'comm_'.$r['id'],
        'title' => $r['name'],
        'body' => 'Commented: ' . substr($r['comment'], 0, 30) . '...',
        'date' => $r['date'],
        'image' => $r['profile_photo'],
        'data' => ['post_id' => $r['post_id'], 'user_id' => $r['user_id']]
    ];
}

// Sort all by date DESC
usort($notifications, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

echo json_encode(['status' => 'success', 'data' => $notifications]);
?>
