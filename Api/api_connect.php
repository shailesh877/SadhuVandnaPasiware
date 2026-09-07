<?php
// api_connect.php - Unified Connection & Request Management
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    include 'connection.php';
    include 'push_helper.php';

    // Handle JSON Input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $action = $_REQUEST['action'] ?? $data['action'] ?? '';
    $user_id = $_REQUEST['user_id'] ?? $data['user_id'] ?? 0;
    
    $limit = intval($_REQUEST['limit'] ?? $data['limit'] ?? 50);
    $offset = intval($_REQUEST['offset'] ?? $data['offset'] ?? 0);

    // Simple Debug Log
    file_put_contents('debug_api.txt', date('Y-m-d H:i:s')." - Action: $action, User: $user_id\n", FILE_APPEND);

    if (!$user_id) {
        echo json_encode(["status" => "error", "message" => "User ID required"]);
        exit;
    }

    $mq = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1");
    $my_profile_id = ($mq && $mq->num_rows > 0) ? $mq->fetch_assoc()['id'] : 0;

    file_put_contents('debug_api.txt', "My Profile ID: $my_profile_id\n", FILE_APPEND);

    // --- FETCH ALL REQUESTS (Received, Sent, Friends) ---
    if ($action == 'fetch_my_requests') {
        if (!$my_profile_id) {
            echo json_encode(["status" => "success", "received" => [], "sent" => [], "connected" => []]);
            exit;
        }

        // Received
        $received = [];
        $rq = $con->query("SELECT p.id AS proposal_id, p.status, mp.id AS id, mp.id AS sender_profile_id, mp.full_name, mp.photo, mp.city, mp.education, mp.caste, mp.occupation, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_proposals p INNER JOIN tbl_marriage_profiles mp ON mp.id = p.sender_id WHERE p.receiver_id='$my_profile_id' AND p.status='pending' ORDER BY p.id DESC LIMIT $limit OFFSET $offset");
        if ($rq) while ($r = $rq->fetch_assoc()) $received[] = $r;

        // Sent
        $sent = [];
        $sq = $con->query("SELECT p.id AS proposal_id, p.status, mp.id AS id, mp.id AS receiver_profile_id, mp.full_name, mp.photo, mp.city, mp.education, mp.caste, mp.occupation, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_proposals p INNER JOIN tbl_marriage_profiles mp ON mp.id = p.receiver_id WHERE p.sender_id='$my_profile_id' AND p.status='pending' ORDER BY p.id DESC LIMIT $limit OFFSET $offset");
        if ($sq) while ($r = $sq->fetch_assoc()) $sent[] = $r;

        // Friends (Matches) - Supporting both 'friend' and 'accepted' status just in case
        $connected = [];
        $cq_query = "
            SELECT p.id AS proposal_id, p.status as proposal_status, mp.id AS id, mp.id AS friend_profile_id, mp.full_name, mp.photo, mp.city, mp.education, mp.caste, mp.occupation, mp.user_id, 
            TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age 
            FROM tbl_proposals p 
            INNER JOIN tbl_marriage_profiles mp ON mp.id = IF(p.sender_id='$my_profile_id', p.receiver_id, p.sender_id) 
            WHERE (p.sender_id='$my_profile_id' OR p.receiver_id='$my_profile_id') 
            AND p.status IN ('friend', 'accepted') 
            ORDER BY p.id DESC LIMIT $limit OFFSET $offset";
        
        $cq = $con->query($cq_query);
        if ($cq) {
            while ($r = $cq->fetch_assoc()) $connected[] = $r;
        }
        
        file_put_contents('debug_api.txt', "Found ".count($connected)." Matches\n", FILE_APPEND);

        echo json_encode(["status" => "success", "received" => $received, "sent" => $sent, "connected" => $connected]);
        exit;
    }

    // --- ACTIONS ---
    if (!$my_profile_id) {
        echo json_encode(["status" => "error", "message" => "Marriage Profile Required"]);
        exit;
    }

    if ($action == 'send_request') {
        $receiver_id = $_REQUEST['receiver_id'] ?? $data['receiver_id'] ?? 0;
        $chk = $con->query("SELECT id FROM tbl_proposals WHERE (sender_id='$my_profile_id' AND receiver_id='$receiver_id') OR (sender_id='$receiver_id' AND receiver_id='$my_profile_id')");
        if ($chk && $chk->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Proposal already exists with status: ".$chk->fetch_assoc()['status']]);
            exit;
        }
        $ins = $con->query("INSERT INTO tbl_proposals (sender_id, receiver_id, profile_id, status) VALUES ('$my_profile_id', '$receiver_id', '$receiver_id', 'pending')");
        if ($ins) echo json_encode(["status" => "success", "message" => "Request Sent"]);
        else echo json_encode(["status" => "error", "message" => "Failed to insert proposal"]);
        exit;
    }

    if ($action == 'accept_request' || $action == 'reject_request' || $action == 'remove_connection' || $action == 'cancel_request') {
        $pid = $_REQUEST['proposal_id'] ?? $data['proposal_id'] ?? 0;
        $other_id = $_REQUEST['sender_id'] ?? $_REQUEST['receiver_id'] ?? $data['sender_id'] ?? $data['receiver_id'] ?? 0;
        $other_user_id = $_REQUEST['other_user_id'] ?? $data['other_user_id'] ?? 0;

        // If we only have Friend's User ID, look up their Profile ID
        if (!$other_id && $other_user_id) {
            $oq = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$other_user_id' LIMIT 1");
            if ($oq && $oq->num_rows > 0) $other_id = $oq->fetch_assoc()['id'];
        }

        if ($action == 'accept_request') {
            if ($pid) $con->query("UPDATE tbl_proposals SET status='friend' WHERE id='$pid'");
            else $con->query("UPDATE tbl_proposals SET status='friend' WHERE sender_id='$other_id' AND receiver_id='$my_profile_id'");
            echo json_encode(["status" => "success", "message" => "Accepted"]);
        } else {
            if ($pid) $con->query("DELETE FROM tbl_proposals WHERE id='$pid'");
            else $con->query("DELETE FROM tbl_proposals WHERE (sender_id='$my_profile_id' AND receiver_id='$other_id') OR (sender_id='$other_id' AND receiver_id='$my_profile_id')");
            echo json_encode(["status" => "success", "message" => "Deleted/Cancelled"]);
        }
        exit;
    }

    echo json_encode(["status" => "error", "message" => "Invalid Action: $action"]);

} catch (Exception $e) {
    file_put_contents('debug_api.txt', "Error: ".$e->getMessage()."\n", FILE_APPEND);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
