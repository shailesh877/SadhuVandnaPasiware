<?php
include 'headers.php';
include 'connection.php';

$message_id = intval($_GET['message_id'] ?? 0);

if (!$message_id) {
    echo json_encode(["status" => "error", "message" => "Message ID is required"]);
    exit;
}

$sql = "SELECT seen_by FROM tbl_group_messages WHERE id = $message_id";
$res = $con->query($sql);
$row = $res->fetch_assoc();

if (!$row) {
    echo json_encode(["status" => "error", "message" => "Message not found"]);
    exit;
}

$seen_by_json = $row['seen_by'] ? json_decode($row['seen_by'], true) : [];
if (!is_array($seen_by_json)) $seen_by_json = [];

$viewers = [];
if (!empty($seen_by_json)) {
    foreach ($seen_by_json as $entry) {
        $u_id = intval($entry['u']);
        $seen_at = $entry['t'];
        
        $u_sql = "SELECT name, profile_photo FROM tbl_members WHERE id = $u_id";
        $u_res = $con->query($u_sql);
        if ($u_row = $u_res->fetch_assoc()) {
            $viewers[] = [
                "name" => $u_row['name'],
                "photo" => $u_row['profile_photo'],
                "seen_at" => $seen_at
            ];
        }
    }
}

// Sort by seen_at descending (most recent first)
usort($viewers, function($a, $b) {
    return strtotime($b['seen_at']) - strtotime($a['seen_at']);
});

echo json_encode(["status" => "success", "data" => $viewers]);
?>
