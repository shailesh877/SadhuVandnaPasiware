<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

try {
    include("connection.php");
    include("push_helper.php");

    // Handle both JSON and FormData
    $json = file_get_contents('php://input');
    $data_input = json_decode($json, true);

    file_put_contents('debug_proposal.txt', date('Y-m-d H:i:s')." | Params: ".json_encode($_REQUEST)." | JSON: $json\n", FILE_APPEND);

    $user_id = intval($_REQUEST['user_id'] ?? $data_input['user_id'] ?? 0);
    $receiver_id = intval($_REQUEST['receiver_id'] ?? $data_input['receiver_id'] ?? 0);

    if (!$user_id || !$receiver_id) {
        throw new Exception("User ID and Receiver ID required");
    }

    // Get Marriage Profile ID for sender
    $mq = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1");
    if (!$mq) throw new Exception("DB Error on sender profile: ".$con->error);
    
    $my_profile_id = ($mq && $mq->num_rows > 0) ? $mq->fetch_assoc()['id'] : 0;

    if (!$my_profile_id) {
        echo json_encode(["status" => "error", "message" => "Please create your marriage profile first."]);
        exit;
    }

    // Check existing
    $chk = $con->query("SELECT id, status FROM tbl_proposals WHERE (sender_id='$my_profile_id' AND receiver_id='$receiver_id') OR (sender_id='$receiver_id' AND receiver_id='$my_profile_id')");
    if ($chk && $chk->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Request already exists with status: " . $chk->fetch_assoc()['status']]);
        exit;
    }

    // Insert
    $ins = $con->query("INSERT INTO tbl_proposals (sender_id, receiver_id, profile_id, status) VALUES ('$my_profile_id', '$receiver_id', '$receiver_id', 'pending')");

    if ($ins) {
        // Send Push
        $sender_name = "Someone";
        $sQ = $con->query("SELECT full_name FROM tbl_marriage_profiles WHERE id = '$my_profile_id' LIMIT 1");
        if ($sRow = $sQ->fetch_assoc()) $sender_name = $sRow['full_name'];

        $rQ = $con->query("SELECT user_id FROM tbl_marriage_profiles WHERE id = '$receiver_id' LIMIT 1");
        if ($rQ && $rRow = $rQ->fetch_assoc()) {
            $real_user_id = $rRow['user_id'];
            sendExpoPushNotification($con, $real_user_id, "New Interest", "$sender_name is interested in your marriage profile.", [
                "type" => "marriage_request",
                "sender_profile_id" => $my_profile_id
            ]);
        }
        echo json_encode(["status" => "success", "message" => "Proposal Sent Successfully!"]);
    } else {
        throw new Exception("DB Error on insert: ".$con->error);
    }

} catch (Exception $e) {
    file_put_contents('debug_proposal.txt', "Error: ".$e->getMessage()."\n", FILE_APPEND);
    echo json_encode(["status" => "error", "message" => "System error occurred"]);
}
?>
