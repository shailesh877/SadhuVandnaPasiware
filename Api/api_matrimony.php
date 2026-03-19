<?php
// Force JSON and CORS immediately
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

    // Marriage Profile Lookup
    $my_profile_id = 0;
    $q = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id'");
    if ($q && $q->num_rows > 0) {
        $my_profile_id = $q->fetch_assoc()['id'];
    }

    if ($action == 'fetch_my_requests') {
        if (!$my_profile_id) {
            echo json_encode(["status" => "success", "sent" => [], "received" => [], "connected" => []]);
            exit;
        }

        $sent = [];
        $sq = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.receiver_id = mp.id WHERE p.sender_id='$my_profile_id' AND p.status='pending'");
        if ($sq) {
            while ($r = $sq->fetch_assoc()) $sent[] = $r;
        }

        $received = [];
        $rq = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, mp.id as sender_profile_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.sender_id = mp.id WHERE p.receiver_id='$my_profile_id' AND p.status='pending'");
        if ($rq) {
            while ($r = $rq->fetch_assoc()) $received[] = $r;
        }

        $connected = []; // Matches
        $cq1 = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, mp.id as friend_profile_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.receiver_id = mp.id WHERE p.sender_id='$my_profile_id' AND p.status IN ('friend', 'accepted')");
        if ($cq1) {
            while ($r = $cq1->fetch_assoc()) $connected[] = $r;
        }

        $cq2 = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, mp.id as friend_profile_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.sender_id = mp.id WHERE p.receiver_id='$my_profile_id' AND p.status IN ('friend', 'accepted')");
        if ($cq2) {
            while ($r = $cq2->fetch_assoc()) $connected[] = $r;
        }

        echo json_encode([
            "status" => "success",
            "sent" => $sent,
            "received" => $received,
            "connected" => $connected
        ]);
        exit;
    }

    if ($action == 'fetch_profiles') {
        // ... concise fetch for debug
        $profiles = [];
        $res = $con->query("SELECT *, TIMESTAMPDIFF(YEAR, STR_TO_DATE(dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_marriage_profiles WHERE id != '$my_profile_id' ORDER BY id DESC LIMIT 20");
        if ($res) {
            while ($row = $res->fetch_assoc()) $profiles[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $profiles]);
        exit;
    }

    echo json_encode(["status" => "error", "message" => "Unknown action: $action"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
