<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Global Error Handler to catch fatal errors and return JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        echo json_encode([
            "status" => "error", 
            "message" => "Fatal Error: " . $error['message'],
            "file" => $error['file'],
            "line" => $error['line']
        ]);
        exit;
    }
});

try {
    include 'headers.php';
    include 'connection.php';
    include 'push_helper.php';

    $action = $_REQUEST['action'] ?? '';
    $user_id = $_REQUEST['user_id'] ?? 0; // The App User ID (tbl_members.id)

    if (!$user_id) {
        echo json_encode(["status" => "error", "message" => "User ID required"]);
        exit;
    }

    // Helper: Get Marriage Profile ID from User ID
    function getProfileId($con, $uid)
    {
        $q = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$uid'");
        return ($q && $q->num_rows > 0) ? $q->fetch_assoc()['id'] : 0;
    }

    $my_profile_id = getProfileId($con, $user_id);

    // 1. FETCH PROFILES (with Filters)
    if ($action == 'fetch_profiles') {
        $gender = $_POST['gender'] ?? '';
        $age_group = $_POST['age'] ?? '';
        $city = $_POST['city'] ?? '';
        $education = $_POST['education'] ?? '';
        $search = $_POST['search'] ?? '';

        $where = " WHERE 1 ";

        if ($gender) $where .= " AND gender = '$gender' ";
        if ($city) $where .= " AND city LIKE '%$city%' ";
        if ($education) $where .= " AND education LIKE '%$education%' ";

        if ($age_group) {
            $parts = explode('-', $age_group);
            if (count($parts) == 2) {
                $min = intval($parts[0]);
                $max = intval($parts[1]);
                $where .= " AND TIMESTAMPDIFF(YEAR, STR_TO_DATE(dob,'%Y-%m-%d'), CURDATE()) BETWEEN $min AND $max ";
            }
        }

        if ($search) {
            $where .= " AND (full_name LIKE '%$search%' OR city LIKE '%$search%' OR caste LIKE '%$search%') ";
        }

        if ($my_profile_id) {
            $where .= " AND id != '$my_profile_id' ";
        }

        $files = [];
        $res = $con->query("SELECT *, TIMESTAMPDIFF(YEAR, STR_TO_DATE(dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_marriage_profiles $where ORDER BY id DESC");

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $status = null;
                if ($my_profile_id) {
                    $pq = $con->query("SELECT status, sender_id FROM tbl_proposals WHERE (sender_id='$my_profile_id' AND receiver_id='{$row['id']}') OR (sender_id='{$row['id']}' AND receiver_id='$my_profile_id') LIMIT 1");
                    if ($pq && $pq->num_rows > 0) {
                        $p = $pq->fetch_assoc();
                        $status = strtolower($p['status']);
                        $is_sender = ($p['sender_id'] == $my_profile_id);
                        if ($status == 'pending') {
                            $status = $is_sender ? 'sent' : 'received';
                        }
                    }
                }
                $row['proposal_status'] = $status;
                $files[] = $row;
            }
        }

        echo json_encode(["status" => "success", "data" => $files]);
        exit;
    }

    // 2. SEND PROPOSAL
    if ($action == 'send_proposal') {
        if (!$my_profile_id) {
            echo json_encode(["status" => "error", "message" => "Please create your marriage profile first."]);
            exit;
        }

        $receiver_id = $_POST['receiver_id'] ?? 0;
        if (!$receiver_id) {
            echo json_encode(["status" => "error", "message" => "Invalid receiver"]);
            exit;
        }

        $chk = $con->query("SELECT id FROM tbl_proposals WHERE (sender_id='$my_profile_id' AND receiver_id='$receiver_id') OR (sender_id='$receiver_id' AND receiver_id='$my_profile_id')");
        if ($chk && $chk->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Request already pending or connected."]);
            exit;
        }

        $ins = $con->query("INSERT INTO tbl_proposals (sender_id, receiver_id, profile_id, status) VALUES ('$my_profile_id', '$receiver_id', '$receiver_id', 'pending')");

        if ($ins) {
            $sender_name = "Someone";
            $sQ = $con->query("SELECT full_name FROM tbl_marriage_profiles WHERE id = '$my_profile_id' LIMIT 1");
            if ($sQ && $sRow = $sQ->fetch_assoc()) $sender_name = $sRow['full_name'];

            $rQ = $con->query("SELECT user_id FROM tbl_marriage_profiles WHERE id = '$receiver_id' LIMIT 1");
            if ($rQ && $rRow = $rQ->fetch_assoc()) {
                sendExpoPushNotification($con, $rRow['user_id'], "New Interest", "$sender_name is interested in your marriage profile.", [
                    "type" => "marriage_request",
                    "sender_profile_id" => $my_profile_id
                ]);
            }
            echo json_encode(["status" => "success", "message" => "Request sent successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error"]);
        }
        exit;
    }

    // 3. CANCEL REQUEST
    if ($action == 'cancel_request') {
        $receiver_id = $_POST['receiver_id'] ?? 0;
        $con->query("DELETE FROM tbl_proposals WHERE sender_id='$my_profile_id' AND receiver_id='$receiver_id' AND status='pending'");
        echo json_encode(["status" => "success", "message" => "Request cancelled"]);
        exit;
    }

    // 4. MANAGE REQUEST
    if ($action == 'manage_request') {
        $sender_id = $_POST['sender_id'] ?? 0;
        $sub_action = $_POST['sub_action'] ?? '';

        if (!$my_profile_id || !$sender_id) {
            echo json_encode(["status" => "error", "message" => "Invalid params"]);
            exit;
        }

        if ($sub_action == 'accept') {
            $con->query("UPDATE tbl_proposals SET status='friend' WHERE sender_id='$sender_id' AND receiver_id='$my_profile_id'");
            $sender_name = "Someone";
            $sQ = $con->query("SELECT full_name FROM tbl_marriage_profiles WHERE id = '$my_profile_id' LIMIT 1");
            if ($sQ && $sRow = $sQ->fetch_assoc()) $sender_name = $sRow['full_name'];

            $rQ = $con->query("SELECT user_id FROM tbl_marriage_profiles WHERE id = '$sender_id' LIMIT 1");
            if ($rQ && $rRow = $rQ->fetch_assoc()) {
                sendExpoPushNotification($con, $rRow['user_id'], "Interest Accepted", "$sender_name accepted your marriage interest!", [
                    "type" => "marriage_accept",
                    "sender_profile_id" => $my_profile_id
                ]);
            }
            echo json_encode(["status" => "success", "message" => "Request Accepted"]);
        } else if ($sub_action == 'reject') {
            $con->query("DELETE FROM tbl_proposals WHERE sender_id='$sender_id' AND receiver_id='$my_profile_id'");
            echo json_encode(["status" => "success", "message" => "Request Rejected"]);
        }
        exit;
    }

    // 5. FETCH MY REQUESTS
    if ($action == 'fetch_my_requests') {
        if (!$my_profile_id) {
            echo json_encode(["status" => "success", "sent" => [], "received" => [], "connected" => []]);
            exit;
        }

        $sent = [];
        $sq = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age, mp.caste FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.receiver_id = mp.id WHERE p.sender_id='$my_profile_id' AND p.status='pending'");
        if ($sq) { while ($r = $sq->fetch_assoc()) $sent[] = $r; }

        $received = [];
        $rq = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age, mp.caste, mp.id as sender_profile_id FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.sender_id = mp.id WHERE p.receiver_id='$my_profile_id' AND p.status='pending'");
        if ($rq) { while ($r = $rq->fetch_assoc()) $received[] = $r; }

        $connected = [];
        $cq1 = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, mp.id as friend_profile_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.receiver_id = mp.id WHERE p.sender_id='$my_profile_id' AND p.status IN ('friend', 'accepted')");
        if ($cq1) { while ($r = $cq1->fetch_assoc()) $connected[] = $r; }

        $cq2 = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, mp.id as friend_profile_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.sender_id = mp.id WHERE p.receiver_id='$my_profile_id' AND p.status IN ('friend', 'accepted')");
        if ($cq2) { while ($r = $cq2->fetch_assoc()) $connected[] = $r; }

        echo json_encode(["status" => "success", "sent" => $sent, "received" => $received, "connected" => $connected]);
        exit;
    }

    echo json_encode(["status" => "error", "message" => "Invalid action"]);

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Exception: " . $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);
}
?>
