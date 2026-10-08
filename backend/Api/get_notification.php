<?php
include("connection.php");
session_start();

$session_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$session_email){ echo json_encode([]); exit; }

// Step 1: get user id
$userQ = $con->query("SELECT id FROM tbl_members WHERE email='$session_email' LIMIT 1");
if($userQ->num_rows == 0){ echo json_encode([]); exit; }
$user_id = $userQ->fetch_assoc()['id'];

// Step 2: get marriage profile id
$mp = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1");
if($mp->num_rows == 0){ echo json_encode([]); exit; }
$my_profile_id = $mp->fetch_assoc()['id'];


// 🔥 FIXED QUERY → always return latest message per sender
$q = $con->query("
    SELECT m1.*, mp.full_name AS sender_name, mp.photo,
    (
        SELECT COUNT(*)
        FROM tbl_messages
        WHERE sender_id = m1.sender_id
        AND receiver_id = '$my_profile_id'
        AND (seen = 0 OR seen IS NULL)
    ) AS unread_count
    FROM tbl_messages m1
    INNER JOIN (
        SELECT sender_id, MAX(id) AS last_id
        FROM tbl_messages
        WHERE receiver_id = '$my_profile_id'
        GROUP BY sender_id
    ) m2 ON m1.id = m2.last_id
    LEFT JOIN tbl_marriage_profiles mp ON m1.sender_id = mp.id
    ORDER BY m1.id and m1.created_at DESC
");

$data = [];

while($row = $q->fetch_assoc()){
    
    $msg_date = $row['date'] ?? $row['created_at'] ?? "";

    $data[] = [
        'id'            => $row['id'],
        'sender_id'     => $row['sender_id'],
        'receiver_id'   => $my_profile_id,
        'name'          => $row['sender_name'],
        'profile'       => $row['photo'],
        'message'       => $row['message'],
        'date'          => $msg_date ? date("d M h:i A", strtotime($msg_date)) : "",
        'unread_count'  => $row['unread_count']
    ];
}

echo json_encode($data);
?>
