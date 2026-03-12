<?php
include 'headers.php';
include 'connection.php';

$my = intval($_GET['my_profile_id'] ?? 0);
$receiver = intval($_GET['receiver_id'] ?? 0);
$platform = $_GET['platform'] ?? 'marriage';

if(!$my || !$receiver){
    echo json_encode(["status" => "error", "message" => "Invalid ID"]);
    exit;
}

// mark seen (filtered by platform)
$con->query("
    UPDATE tbl_messages
    SET seen=1, seen_at=NOW()
    WHERE receiver_id={$my} AND sender_id={$receiver} AND seen=0 AND chat_platform='{$platform}'
");

// fetch messages (filtered by platform)
$sql = "
    SELECT id, sender_id, receiver_id, message, seen, created_at
    FROM tbl_messages
    WHERE ((sender_id={$my} AND receiver_id={$receiver})
       OR (sender_id={$receiver} AND receiver_id={$my}))
       AND chat_platform='{$platform}'
    ORDER BY created_at ASC
";
$res = $con->query($sql);

$messages = [];
while($r = $res->fetch_assoc()){
    $messages[] = [
        "id" => $r['id'],
        "text" => $r['message'],
        "sender_id" => $r['sender_id'],
        "seen" => $r['seen'],
        "created_at" => $r['created_at']
    ];
}

echo json_encode(["status" => "success", "data" => $messages]);
?>
