<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    include 'connection.php';
    include 'push_helper.php';

    if (!$con) {
        echo json_encode(["status" => "error", "message" => "Connection failed"]);
        exit;
    }

    $action = $_REQUEST['action'] ?? '';
    $user_id = $_REQUEST['user_id'] ?? 0;

    if (!$user_id) {
        echo json_encode(["status" => "error", "message" => "User ID required"]);
        exit;
    }

    // 1. Get Current User's Marriage Profile ID (Exactly as website)
    // Website uses session, we use user_id passed from App
    $user_profile_q = $con->query("
        SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1
    ");
    $my_profile_id = ($user_profile_q && $user_profile_q->num_rows > 0) ? $user_profile_q->fetch_assoc()['id'] : 0;

    // 2. FETCH MY REQUESTS (Received & Sent & Connected)
    if ($action == 'fetch_my_requests') {
        if (!$my_profile_id) {
            echo json_encode(["status" => "success", "sent" => [], "received" => [], "connected" => []]);
            exit;
        }

        // Received (From view_request.php logic)
        $received = [];
        $rq = $con->query("
            SELECT p.id AS proposal_id, p.status, mp.id AS sender_profile_id, mp.id as id, mp.full_name, mp.city, mp.education, mp.photo, mp.caste, mp.occupation, 
            TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age
            FROM tbl_proposals p
            INNER JOIN tbl_marriage_profiles mp ON mp.id = p.sender_id
            WHERE p.receiver_id='$my_profile_id' AND p.status='pending'
            ORDER BY p.id DESC
        ");
        if ($rq) {
            while ($r = $rq->fetch_assoc()) $received[] = $r;
        }

        // Sent
        $sent = [];
        $sq = $con->query("
            SELECT p.id AS proposal_id, p.status, mp.id AS receiver_profile_id, mp.id as id, mp.full_name, mp.city, mp.education, mp.photo, mp.caste, mp.occupation, 
            TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age
            FROM tbl_proposals p
            INNER JOIN tbl_marriage_profiles mp ON mp.id = p.receiver_id
            WHERE p.sender_id='$my_profile_id' AND p.status='pending'
            ORDER BY p.id DESC
        ");
        if ($sq) {
            while ($r = $sq->fetch_assoc()) $sent[] = $r;
        }

        // Connected (From connected.php logic)
        $connected = [];
        $cq = $con->query("
            SELECT p.id AS proposal_id, p.status as proposal_status, mp.id AS friend_profile_id, mp.id as id, mp.full_name, mp.city, mp.education, mp.photo, mp.caste, mp.occupation, mp.user_id,
            TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age
            FROM tbl_proposals p
            INNER JOIN tbl_marriage_profiles mp ON mp.id = IF(p.sender_id='$my_profile_id', p.receiver_id, p.sender_id)
            WHERE (p.sender_id='$my_profile_id' OR p.receiver_id='$my_profile_id')
            AND p.status='friend'
            ORDER BY p.id DESC
        ");
        if ($cq) {
            while ($r = $cq->fetch_assoc()) $connected[] = $r;
        }

        echo json_encode([
            "status" => "success",
            "received" => $received,
            "sent" => $sent,
            "connected" => $connected
        ]);
        exit;
    }

    // 3. ACTIONS (Accept/Reject/Cancel)
    if ($action == 'accept_request') {
        $sender_id = $_REQUEST['sender_id'] ?? 0;
        $con->query("UPDATE tbl_proposals SET status='friend' WHERE sender_id='$sender_id' AND receiver_id='$my_profile_id'");
        echo json_encode(["status" => "success", "message" => "Accepted"]);
        exit;
    }

    if ($action == 'reject_request' || $action == 'remove_connection' || $action == 'cancel_request') {
        $other_id = $_REQUEST['receiver_id'] ?? $_REQUEST['sender_id'] ?? 0;
        $con->query("DELETE FROM tbl_proposals WHERE (sender_id='$my_profile_id' AND receiver_id='$other_id') OR (sender_id='$other_id' AND receiver_id='$my_profile_id')");
        echo json_encode(["status" => "success", "message" => "Removed/Cancelled"]);
        exit;
    }

    echo json_encode(["status" => "error", "message" => "Invalid Action"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
