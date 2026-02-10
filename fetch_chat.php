<?php
include("connection.php");
session_start();
date_default_timezone_set('Asia/Kolkata');

$my = intval($_GET['my_profile_id'] ?? 0);
$receiver = intval($_GET['receiver_id'] ?? 0);
$last_id = intval($_GET['last_id'] ?? 0);

if(!$my || !$receiver){
    echo "<div class='text-center text-gray-400'>No chat found.</div>";
    exit;
}

/* seen */
if($last_id == 0) {
    // Only mark seen if we are fetching the whole chat or significant updates, 
    // though typically seeing the message implies we've read it. 
    // Doing it here is fine.
    $con->query("
    UPDATE tbl_messages 
    SET seen=1
    WHERE receiver_id=$my AND sender_id=$receiver AND seen=0
    ");
} else {
   // Also mark new messages as seen when we fetch them incrementally
    $con->query("
    UPDATE tbl_messages 
    SET seen=1
    WHERE receiver_id=$my AND sender_id=$receiver AND seen=0 AND id > $last_id
    ");
}

/* Build Query */
$sql_where = "
(
 (sender_id=$my AND receiver_id=$receiver AND deleted_by_sender=0)
 OR
 (sender_id=$receiver AND receiver_id=$my AND deleted_by_receiver=0)
)
";

if($last_id > 0){
    $sql_where .= " AND id > $last_id";
}

$sql = "SELECT * FROM tbl_messages WHERE $sql_where ORDER BY created_at ASC";

$res = $con->query($sql);

// If last_id is requested, we return JSON
if($last_id > 0){
    header('Content-Type: application/json');
    $data = [];
    while($r = $res->fetch_assoc()){
        $isMine = ($r['sender_id'] == $my);
        $msg = "";
        
        /* FILE */
        if(!empty($r['file'])){
            $safe = htmlspecialchars($r['file']);
            if($r['file_type'] === 'image'){
                $msg .= "
                <img src='{$safe}'
                     class='max-h-[320px] rounded-xl shadow cursor-pointer'
                     onclick=\"this.classList.toggle('scale-150')\">";
            }else{
                $msg .= "
                <video controls class='max-h-[420px] w-full rounded-xl shadow'>
                    <source src='{$safe}'>
                </video>";
            }
        }
    
        /* TEXT */
        if(!empty($r['message'])){
            $msg .= "
            <div class='mt-2'>
                ".nl2br(htmlspecialchars($r['message']))."
            </div>";
        }
    
        $time = date("h:i A", strtotime($r['created_at']));
        $seen = ($isMine && $r['seen']) ? "Seen" : "";
        $deleteBtn = "<span class='delete-btn text-red-400 text-[10px] cursor-pointer ml-2' data-id='{$r['id']}'>Delete</span>";
    
        if($isMine){
            $html = "
            <div class='flex flex-col items-end mb-3 sent-msg' data-msg-id='{$r['id']}'>
                <div class='bg-white border border-orange-200 px-4 py-2 rounded-xl rounded-br-sm max-w-[75%]'>
                    {$msg}
                </div>
                <div class='text-[11px] text-gray-400 mt-1 flex gap-2'>
                    {$time} <span class='msg-seen-status text-blue-500'>{$seen}</span> {$deleteBtn}
                </div>
            </div>";
        }else{
            $html = "
            <div class='flex flex-col items-start mb-3' data-msg-id='{$r['id']}'>
                <div class='bg-white border border-gray-200 px-4 py-2 rounded-xl rounded-bl-sm max-w-[75%]'>
                    {$msg}
                </div>
                <div class='text-[11px] text-gray-400 mt-1 flex items-center'>
                    {$time} {$deleteBtn}
                </div>
            </div>";
        }

        $data[] = [
            'id' => $r['id'],
            'html' => $html
        ];
    }
    echo json_encode($data);
    exit;
}

// Old behavior: Return full HTML string
$html = "";
while($r = $res->fetch_assoc()){
    $isMine = ($r['sender_id'] == $my);
    $msg = "";

    /* FILE */
    if(!empty($r['file'])){
        $safe = htmlspecialchars($r['file']);

        if($r['file_type'] === 'image'){
            $msg .= "
            <img src='{$safe}'
                 class='max-h-[320px] rounded-xl shadow cursor-pointer'
                 onclick=\"this.classList.toggle('scale-150')\">";
        }else{
            $msg .= "
            <video controls class='max-h-[420px] w-full rounded-xl shadow'>
                <source src='{$safe}'>
            </video>";
        }
    }

    /* TEXT */
    if(!empty($r['message'])){
        $msg .= "
        <div class='mt-2'>
            ".nl2br(htmlspecialchars($r['message']))."
        </div>";
    }

    $time = date("h:i A", strtotime($r['created_at']));
    $seen = ($isMine && $r['seen']) ? "Seen" : "";

    // Always allow delete (soft delete logic handles the rest)
    $deleteBtn = "<span class='delete-btn text-red-400 text-[10px] cursor-pointer ml-2' data-id='{$r['id']}'>Delete</span>";

    if($isMine){
        $html .= "
        <div class='flex flex-col items-end mb-3 sent-msg' data-msg-id='{$r['id']}'>
            <div class='bg-white border border-orange-200 px-4 py-2 rounded-xl rounded-br-sm max-w-[75%]'>
                {$msg}
            </div>
            <div class='text-[11px] text-gray-400 mt-1 flex gap-2'>
                {$time} <span class='msg-seen-status text-blue-500'>{$seen}</span> {$deleteBtn}
            </div>
        </div>";
    }else{
        $html .= "
        <div class='flex flex-col items-start mb-3' data-msg-id='{$r['id']}'>
            <div class='bg-white border border-gray-200 px-4 py-2 rounded-xl rounded-bl-sm max-w-[75%]'>
                {$msg}
            </div>
            <div class='text-[11px] text-gray-400 mt-1 flex items-center'>
                {$time} {$deleteBtn}
            </div>
        </div>";
    }
}

echo $html ?: "<div class='text-center text-gray-400 mt-5' id='no-msg'>No messages yet.</div>";