<?php
include 'headers.php';
include 'connection.php';

date_default_timezone_set('Asia/Kolkata');

$my       = intval($_GET['my_profile_id'] ?? 0);
$receiver = intval($_GET['receiver_id'] ?? 0);
$last_id  = intval($_GET['last_id'] ?? 0);

if(!$my || !$receiver){
    echo json_encode([
        "status" => "error",
        "message" => "Invalid data"
    ]);
    exit;
}

/* MARK SEEN */
if($last_id == 0){
    $con->query("
        UPDATE tbl_messages 
        SET seen=1
        WHERE receiver_id=$my 
          AND sender_id=$receiver 
          AND seen=0
    ");
} else {
    $con->query("
        UPDATE tbl_messages 
        SET seen=1
        WHERE receiver_id=$my 
          AND sender_id=$receiver 
          AND seen=0 
          AND id > $last_id
    ");
}

/* BUILD QUERY */
$where = "
(
 (sender_id=$my AND receiver_id=$receiver AND deleted_by_sender=0)
 OR
 (sender_id=$receiver AND receiver_id=$my AND deleted_by_receiver=0)
)
";

if($last_id > 0){
    $where .= " AND id > $last_id";
}

$sql = "SELECT * FROM tbl_messages WHERE $where ORDER BY id ASC";
$res = $con->query($sql);

$data = [];

while($r = $res->fetch_assoc()){
    $data[] = [
        "id"          => (int)$r['id'],
        "sender_id"   => (int)$r['sender_id'],
        "receiver_id" => (int)$r['receiver_id'],
        "is_mine"     => ($r['sender_id'] == $my),
        "message"     => $r['message'],
        "file"        => $r['file'],
        "file_type"   => $r['file_type'], // image | video | null
        "seen"        => (int)$r['seen'],
        "created_at"  => $r['created_at'],
        "time"        => date("h:i A", strtotime($r['created_at']))
    ];
}

echo json_encode([
    "status" => "success",
    "count"  => count($data),
    "data"   => $data
]);
