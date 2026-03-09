<?php
include("connection.php");

$call_id = intval($_POST['call_id'] ?? 0);
$status = $_POST['status'] ?? '';
$duration = intval($_POST['duration'] ?? 0);

if(!$call_id || !in_array($status, ['accepted', 'rejected', 'ended'])){
    echo "error";
    exit;
}

// Get call details
$q = $con->query("SELECT caller_id, receiver_id, type, status, chat_platform FROM tbl_calls WHERE id=$call_id");
$call_data = $q->fetch_assoc();
if (!$call_data) {
    echo "error";
    exit;
}

// Prevent duplicate logs if already ended or rejected
$already_finished = in_array($call_data['status'], ['ended', 'rejected']);

if ($status === 'ended') {
    // If duration is passed, use it. Otherwise, if it was accepted, we might want to calculate it, 
    // but the client-side timer is usually more accurate.
    $stmt = $con->prepare("UPDATE tbl_calls SET status=?, duration=? WHERE id=?");
    $stmt->bind_param("sii", $status, $duration, $call_id);
} else {
    $stmt = $con->prepare("UPDATE tbl_calls SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $call_id);
}

// Clear any buffers before output
while (ob_get_level() > 0) ob_end_clean();
if($stmt->execute()){
    if (in_array($status, ['ended', 'rejected']) && !$already_finished) {
        $caller_id = $call_data['caller_id'];
        $receiver_id = $call_data['receiver_id'];
        $type = $call_data['type'];
        $chat_platform = $call_data['chat_platform'];
        
        $msg_text = "";
        if ($status === 'rejected') {
            $msg_text = "❌ Rejected " . ucfirst($type) . " Call";
        } else if ($status === 'ended' && $call_data['status'] === 'ringing') {
            $msg_text = "❌ Missed " . ucfirst($type) . " Call";
        } else if ($status === 'ended') {
            $msg_text = "📞 " . ucfirst($type) . " Call Ended";
            if ($duration > 0) {
                 $mins = floor($duration / 60);
                 $secs = $duration % 60;
                 $duration_str = str_pad($mins, 2, "0", STR_PAD_LEFT) . ":" . str_pad($secs, 2, "0", STR_PAD_LEFT);
                 $msg_text .= " (Talking Time: $duration_str)";
            }
        }
        
        $pref = "SYSTEM_CALL:";
        $final_msg = $pref . $msg_text;
        
        $seen = 0; 
        if ($status === 'ended' && $call_data['status'] !== 'ringing') {
            $seen = 1; 
        }
        
        $ins = $con->prepare("INSERT INTO tbl_messages (sender_id, receiver_id, message, chat_platform, seen) VALUES (?, ?, ?, ?, ?)");
        $ins->bind_param("iissi", $caller_id, $receiver_id, $final_msg, $chat_platform, $seen);
        $ins->execute();
    }
    echo "ok";
} else {
    echo "error";
}
?>
