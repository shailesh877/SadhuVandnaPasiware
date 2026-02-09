<?php
include("connection.php");
session_start();
date_default_timezone_set('Asia/Kolkata');

$my = intval($_GET['my_profile_id'] ?? 0);
$receiver = intval($_GET['receiver_id'] ?? 0);

if(!$my || !$receiver){
    echo "<div class='text-center text-gray-500'>No chat found.</div>";
    exit;
}

// mark seen
$con->query("
    UPDATE tbl_messages
    SET seen=1, seen_at=NOW()
    WHERE receiver_id={$my} AND sender_id={$receiver} AND seen=0
");

// fetch messages
$sql = "
    SELECT id, sender_id, receiver_id, message, seen, created_at
    FROM tbl_messages
    WHERE (sender_id={$my} AND receiver_id={$receiver})
       OR (sender_id={$receiver} AND receiver_id={$my})
    ORDER BY created_at ASC
";
$res = $con->query($sql);

$html = "";

while($r = $res->fetch_assoc()){

    $isMine = ($r["sender_id"] == $my);
    $msg = nl2br(htmlspecialchars($r["message"]));
    $time = date("h:i A", strtotime($r["created_at"]));

    // SEEN separate block
    $seenText = "";
    if($isMine && $r["seen"]==1){
        $seenText = "Seen";
    }

    // delete
    $delete = $isMine 
        ? "<span class='text-[10px] text-red-400 cursor-pointer delete-btn' data-id='{$r['id']}'>Delete</span>"
        : "";

    if($isMine){
        // MY MESSAGE
        $html .= "
        <div class='w-full flex flex-col items-end mb-3'>

            <div class='max-w-[75%] bg-white border border-orange-200 px-4 py-2 
                        rounded-xl rounded-br-sm shadow-sm text-gray-800 text-[15px]'>
                {$msg}
            </div>

            <div class='flex items-center gap-3 mt-1 text-[11px] text-gray-400'>
                <span>{$time}</span>
                <span>{$seenText}</span>
                {$delete}
            </div>

        </div>
        ";

    } else {
        // OTHER MESSAGE
        $html .= "
        <div class='w-full flex flex-col items-start mb-3'>

            <div class='max-w-[75%] bg-white border border-gray-200 px-4 py-2 
                        rounded-xl rounded-bl-sm shadow-sm text-gray-900 text-[15px]'>
                {$msg}
            </div>

            <div class='text-[11px] text-gray-400 mt-1'>
                {$time}
            </div>

        </div>
        ";
    }
}

echo $html ?: "<div class='text-center text-gray-400 mt-5'>No messages yet.</div>";
?>
