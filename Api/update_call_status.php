<?php
include("connection.php");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

date_default_timezone_set("Asia/Kolkata");

// Handle JSON Input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$call_id = intval($_REQUEST['call_id'] ?? $data['call_id'] ?? 0);
$channel_id = $_REQUEST['channel_id'] ?? $data['channel_id'] ?? '';
$status  = $_REQUEST['status'] ?? $data['status'] ?? '';

$allowed_status = ['accepted', 'rejected', 'ended', 'ringing', 'video_request', 'video_accepted', 'video_rejected'];

if ((!$call_id && !$channel_id) || !in_array($status, $allowed_status)) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid parameters"
    ]);
    exit;
}

try {
    // 1. Fetch existing call data to get duration if ending/rejecting
    $caller_id = 0;
    $receiver_id = 0;
    $platform = 'marriage';
    $type = 'audio';
    $created_at = null;
    $prev_status = '';

    if ($call_id) {
        $q = $con->prepare("SELECT caller_id, receiver_id, platform, type, created_at, status FROM tbl_calls WHERE id = ?");
        $q->bind_param("i", $call_id);
    } else {
        $q = $con->prepare("SELECT caller_id, receiver_id, platform, type, created_at, status FROM tbl_calls WHERE caller_peer_id = ? ORDER BY id DESC LIMIT 1");
        $q->bind_param("s", $channel_id);
    }
    
    if ($q->execute()) {
        $res = $q->get_result();
        if ($row = $res->fetch_assoc()) {
            $caller_id = $row['caller_id'];
            $receiver_id = $row['receiver_id'];
            $platform = $row['platform'];
            $type = $row['type'];
            $created_at = $row['created_at'];
            $prev_status = $row['status'];
        }
    }

    // 2. Update the status
    if ($call_id) {
        $stmt = $con->prepare("UPDATE tbl_calls SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $call_id);
    } else {
        $stmt = $con->prepare("UPDATE tbl_calls SET status = ?, updated_at = NOW() WHERE caller_peer_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("ss", $status, $channel_id);
    }

$duration = intval($_REQUEST['duration'] ?? $data['duration'] ?? -1);

if ($stmt->execute()) {
        
        // 3. Insert Call History Message if ended/rejected
        // Only do this if it wasn't already ended/rejected to prevent duplicates
        if (in_array($status, ['ended', 'rejected']) && !in_array($prev_status, ['ended', 'rejected']) && $caller_id && $receiver_id) {
            
            $msg_text = "";
            $icon = $type === 'video' ? '📹' : '📞';
            
            if ($status === 'rejected' || ($status === 'ended' && $duration === 0)) {
                $msg_text = "$icon Missed/Rejected " . ucfirst($type) . " Call";
            } else {
                // Use frontend duration if passed, else fallback to created_at
                $diff = 0;
                if ($duration > 0) {
                    $diff = $duration;
                } elseif ($created_at) {
                    $start = strtotime($created_at);
                    $end = time();
                    $diff = $end - $start;
                }

                $durationStr = "";
                if ($diff > 0) {
                    $mins = floor($diff / 60);
                    $secs = $diff % 60;
                    if ($mins > 0) {
                        $durationStr = " ($mins" . "m $secs" . "s)";
                    } else {
                        $durationStr = " ($secs" . "s)";
                    }
                }
                $msg_text = "$icon " . ucfirst($type) . " Call Ended$durationStr";
            }

            // Insert into tbl_messages
            $mstmt = $con->prepare("INSERT INTO tbl_messages (sender_id, receiver_id, message, chat_platform, seen, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            if ($mstmt) {
                // We always insert the history message sent from the caller to the receiver
                $mstmt->bind_param("iiss", $caller_id, $receiver_id, $msg_text, $platform);
                $mstmt->execute();
            }
        }

        echo json_encode([
            "status" => true,
            "message" => "Call status updated",
            "new_status" => $status
        ]);
    } else {
        echo json_encode([
            "status" => false,
            "message" => "Database update failed"
        ]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => false, "message" => $e->getMessage()]);
}
?>
