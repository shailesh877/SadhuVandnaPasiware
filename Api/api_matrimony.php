<?php
// CRITICAL DEBUGGING - THIS WILL CREATE debug_log.txt IN THE Api FOLDER
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - API STARTED\n", FILE_APPEND);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Global Error Handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        $msg = "Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'];
        file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
        echo json_encode(["status" => "error", "message" => $msg]);
        exit;
    }
});

try {
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Before Headers\n", FILE_APPEND);
    include 'headers.php';
    
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Before Connection\n", FILE_APPEND);
    include 'connection.php';
    
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Before Push Helper\n", FILE_APPEND);
    include 'push_helper.php';

    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - All Includes Done\n", FILE_APPEND);

    $action = $_REQUEST['action'] ?? '';
    $user_id = $_REQUEST['user_id'] ?? 0;

    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Action: $action, UserID: $user_id\n", FILE_APPEND);

    if (!$user_id) {
        echo json_encode(["status" => "error", "message" => "User ID required"]);
        exit;
    }

    function getProfileId($con, $uid)
    {
        $q = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$uid'");
        return ($q && $q->num_rows > 0) ? $q->fetch_assoc()['id'] : 0;
    }

    $my_profile_id = getProfileId($con, $user_id);
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - My Profile ID: $my_profile_id\n", FILE_APPEND);

    // 1. FETCH PROFILES
    if ($action == 'fetch_profiles') {
        // ... (Same logic as before, just kept concise for debug version)
        $files = [];
        $res = $con->query("SELECT *, TIMESTAMPDIFF(YEAR, STR_TO_DATE(dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_marriage_profiles WHERE id != '$my_profile_id' ORDER BY id DESC");
        if ($res) {
            while ($row = $res->fetch_assoc()) $files[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $files]);
        exit;
    }

    // 5. FETCH MY REQUESTS
    if ($action == 'fetch_my_requests') {
        if (!$my_profile_id) {
            file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Returning Empty (No My Profile)\n", FILE_APPEND);
            echo json_encode(["status" => "success", "sent" => [], "received" => [], "connected" => []]);
            exit;
        }

        $sent = [];
        $sq = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.receiver_id = mp.id WHERE p.sender_id='$my_profile_id' AND p.status='pending'");
        if ($sq) while ($r = $sq->fetch_assoc()) $sent[] = $r;

        $received = [];
        $rq = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age, mp.id as sender_profile_id FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.sender_id = mp.id WHERE p.receiver_id='$my_profile_id' AND p.status='pending'");
        if ($rq) while ($r = $rq->fetch_assoc()) $received[] = $r;

        $connected = [];
        $cq1 = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, mp.id as friend_profile_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.receiver_id = mp.id WHERE p.sender_id='$my_profile_id' AND p.status IN ('friend', 'accepted')");
        if ($cq1) while ($r = $cq1->fetch_assoc()) $connected[] = $r;

        $cq2 = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, mp.id as friend_profile_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.sender_id = mp.id WHERE p.receiver_id='$my_profile_id' AND p.status IN ('friend', 'accepted')");
        if ($cq2) while ($r = $cq2->fetch_assoc()) $connected[] = $r;

        file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Success Returning Requests\n", FILE_APPEND);
        echo json_encode(["status" => "success", "sent" => $sent, "received" => $received, "connected" => $connected]);
        exit;
    }

    echo json_encode(["status" => "error", "message" => "Invalid action"]);

} catch (Throwable $e) {
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["status" => "error", "message" => "Exception: " . $e->getMessage()]);
}
?>
