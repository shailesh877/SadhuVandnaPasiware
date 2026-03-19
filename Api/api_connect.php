<?php
// api_connect.php - Unified Connection Management (Website Mirror Logic)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    include 'connection.php';
    include 'push_helper.php';

    $action = $_REQUEST['action'] ?? '';
    $user_id = $_REQUEST['user_id'] ?? 0;

    if (!$user_id) {
        echo json_encode(["status" => "error", "message" => "User ID required"]);
        exit;
    }

    // Get My Profile ID
    $mq = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id'");
    $my_profile_id = ($mq && $mq->num_rows > 0) ? $mq->fetch_assoc()['id'] : 0;

    if (!$my_profile_id) {
        echo json_encode(["status" => "error", "message" => "Marriage Profile not found"]);
        exit;
    }

    // 1. SEND REQUEST
    if ($action == 'send_request') {
        $receiver_id = $_REQUEST['receiver_id'] ?? 0;
        if (!$receiver_id) {
            echo json_encode(["status" => "error", "message" => "Receiver required"]);
            exit;
        }

        $chk = $con->query("SELECT id FROM tbl_proposals WHERE (sender_id='$my_profile_id' AND receiver_id='$receiver_id') OR (sender_id='$receiver_id' AND receiver_id='$my_profile_id')");
        if ($chk && $chk->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Connection already exists"]);
            exit;
        }

        $ins = $con->query("INSERT INTO tbl_proposals (sender_id, receiver_id, profile_id, status) VALUES ('$my_profile_id', '$receiver_id', '$receiver_id', 'pending')");
        if ($ins) {
            // Push notification
            $sender_name = "Someone";
            $sQ = $con->query("SELECT full_name FROM tbl_marriage_profiles WHERE id = '$my_profile_id' LIMIT 1");
            if ($sQ && $sRow = $sQ->fetch_assoc()) $sender_name = $sRow['full_name'];

            $rQ = $con->query("SELECT user_id FROM tbl_marriage_profiles WHERE id = '$receiver_id' LIMIT 1");
            if ($rQ && $rRow = $rQ->fetch_assoc()) {
                sendExpoPushNotification($con, $rRow['user_id'], "New Interest", "$sender_name is interested in your profile.", ["type" => "marriage_request"]);
            }
            echo json_encode(["status" => "success", "message" => "Request Sent"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to send"]);
        }
        exit;
    }

    // 2. ACCEPT / REJECT / REMOVE (Using Proposal ID or Profile IDs)
    if ($action == 'accept_request' || $action == 'reject_request' || $action == 'remove_connection' || $action == 'cancel_request') {
        $proposal_id = $_REQUEST['proposal_id'] ?? 0;
        $other_id = $_REQUEST['sender_id'] ?? $_REQUEST['receiver_id'] ?? $_REQUEST['other_id'] ?? 0;

        if ($action == 'accept_request') {
            if ($proposal_id) {
                $con->query("UPDATE tbl_proposals SET status='friend' WHERE id='$proposal_id' AND receiver_id='$my_profile_id'");
            } else {
                $con->query("UPDATE tbl_proposals SET status='friend' WHERE sender_id='$other_id' AND receiver_id='$my_profile_id'");
            }
            echo json_encode(["status" => "success", "message" => "Accepted"]);
        } else {
            // Delete logic (Reject / Cancel / Remove)
            if ($proposal_id) {
                $con->query("DELETE FROM tbl_proposals WHERE id='$proposal_id'");
            } else {
                $con->query("DELETE FROM tbl_proposals WHERE (sender_id='$my_profile_id' AND receiver_id='$other_id') OR (sender_id='$other_id' AND receiver_id='$my_profile_id')");
            }
            echo json_encode(["status" => "success", "message" => "Deleted/Cancelled"]);
        }
        exit;
    }

    echo json_encode(["status" => "error", "message" => "Invalid Action"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
