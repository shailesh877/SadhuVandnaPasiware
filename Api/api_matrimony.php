<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'headers.php';
include 'connection.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$user_id = $_REQUEST['user_id'] ?? 0; // The App User ID (tbl_members.id)

if(!$user_id){
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

// Helper: Get Marriage Profile ID from User ID
function getProfileId($con, $uid){
    $q = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$uid'");
    return ($q && $q->num_rows > 0) ? $q->fetch_assoc()['id'] : 0;
}

$my_profile_id = getProfileId($con, $user_id);


// 1. FETCH PROFILES (with Filters)
if($action == 'fetch_profiles'){
    $gender = $_POST['gender'] ?? '';
    $age_group = $_POST['age'] ?? '';
    $city = $_POST['city'] ?? '';
    $education = $_POST['education'] ?? '';
    $search = $_POST['search'] ?? '';

    $where = " WHERE 1 ";

    if($gender) $where .= " AND gender = '$gender' ";
    if($city) $where .= " AND city LIKE '%$city%' ";
    if($education) $where .= " AND education LIKE '%$education%' ";

    // Age Filter
    if($age_group){
        $parts = explode('-', $age_group);
        if(count($parts)==2){
            $min = intval($parts[0]);
            $max = intval($parts[1]);
            // Age = (CURDATE - dob)
            // dob BETWEEN CURDATE - max years AND CURDATE - min years
            $where .= " AND TIMESTAMPDIFF(YEAR, STR_TO_DATE(dob,'%Y-%m-%d'), CURDATE()) BETWEEN $min AND $max ";
        }
    }

    if($search){
        $where .= " AND (full_name LIKE '%$search%' OR city LIKE '%$search%' OR caste LIKE '%$search%') ";
    }

    // Exclude own profile
    if($my_profile_id){
        $where .= " AND id != '$my_profile_id' ";
    }

    $files = [];
    $res = $con->query("SELECT *, TIMESTAMPDIFF(YEAR, STR_TO_DATE(dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_marriage_profiles $where ORDER BY id DESC");
    
    while($row = $res->fetch_assoc()){
        // Check proposal status if logged in
        $status = null;
        if($my_profile_id){
             // Check if I sent or Received
             $pq = $con->query("SELECT status, sender_id FROM tbl_proposals WHERE (sender_id='$my_profile_id' AND receiver_id='{$row['id']}') OR (sender_id='{$row['id']}' AND receiver_id='$my_profile_id') LIMIT 1");
             if($pq->num_rows > 0){
                 $p = $pq->fetch_assoc();
                 $status = $p['status']; // pending, friend, etc
                 $is_sender = ($p['sender_id'] == $my_profile_id);
                 if($status == 'pending'){
                     $status = $is_sender ? 'sent' : 'received';
                 }
             }
        }

        $row['proposal_status'] = $status;
        $files[] = $row;
    }

    echo json_encode(["status" => "success", "data" => $files]);
    exit;
}

// 2. SEND PROPOSAL
if($action == 'send_proposal'){
    if(!$my_profile_id){
        echo json_encode(["status" => "error", "message" => "Please create your marriage profile first."]);
        exit;
    }
    
    $receiver_id = $_POST['receiver_id'] ?? 0;
    if(!$receiver_id){
        echo json_encode(["status" => "error", "message" => "Invalid receiver"]);
        exit;
    }

    // Check existing
    $chk = $con->query("SELECT id FROM tbl_proposals WHERE (sender_id='$my_profile_id' AND receiver_id='$receiver_id') OR (sender_id='$receiver_id' AND receiver_id='$my_profile_id')");
    if($chk->num_rows > 0){
         echo json_encode(["status" => "error", "message" => "Request already pending or connected."]);
         exit;
    }

    // Insert
    $ins = $con->query("INSERT INTO tbl_proposals (sender_id, receiver_id, profile_id, status) VALUES ('$my_profile_id', '$receiver_id', '$receiver_id', 'pending')");
    
    if($ins){
        echo json_encode(["status" => "success", "message" => "Request sent successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error"]);
    }
    exit;
}

// 3. CANCEL REQUEST (Sent by me)
if($action == 'cancel_request'){
    $receiver_id = $_POST['receiver_id'] ?? 0;
    $con->query("DELETE FROM tbl_proposals WHERE sender_id='$my_profile_id' AND receiver_id='$receiver_id' AND status='pending'");
    echo json_encode(["status" => "success", "message" => "Request cancelled"]);
    exit;
}

// 4. MANAGE REQUEST (Accept/Reject incoming)
if($action == 'manage_request'){
    $sender_id = $_POST['sender_id'] ?? 0; // The one who sent me request
    $sub_action = $_POST['sub_action'] ?? ''; // accept / reject

    if(!$my_profile_id || !$sender_id) { echo json_encode(["status"=>"error"]); exit; }

    if($sub_action == 'accept'){
        $con->query("UPDATE tbl_proposals SET status='friend' WHERE sender_id='$sender_id' AND receiver_id='$my_profile_id'");
        echo json_encode(["status" => "success", "message" => "Request Accepted"]);
    } else if($sub_action == 'reject'){
        $con->query("DELETE FROM tbl_proposals WHERE sender_id='$sender_id' AND receiver_id='$my_profile_id'");
        echo json_encode(["status" => "success", "message" => "Request Rejected"]);
    }
    exit;
}

// 6. DELETE PROPOSAL (Reject / Remove / Cancel by ID)
if($action == 'delete_proposal'){
    $proposal_id = $_POST['proposal_id'] ?? 0;
    
    if(!$proposal_id){
        echo json_encode(["status"=>"error", "message"=>"ID required"]);
        exit;
    }
    
    $con->query("DELETE FROM tbl_proposals WHERE id='$proposal_id'");
    
    // Check if it's really deleted or just verify success
    echo json_encode(["status" => "success", "message" => "Deleted successfully"]);
    exit;
}

// 5. FETCH MY REQUESTS (Incoming & Outgoing)
if($action == 'fetch_my_requests'){
    if(!$my_profile_id){
        echo json_encode(["status" => "success", "sent" => [], "received" => []]);
        exit;
    }

    // Sent
    $sent = [];
    $sq = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age, mp.caste FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.receiver_id = mp.id WHERE p.sender_id='$my_profile_id' AND p.status='pending'");
    while($r = $sq->fetch_assoc()) $sent[] = $r;

    // Received
    $received = [];
    $rq = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age, mp.caste, mp.id as sender_profile_id FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.sender_id = mp.id WHERE p.receiver_id='$my_profile_id' AND p.status='pending'");
    while($r = $rq->fetch_assoc()) $received[] = $r;

    // Connected (Friends) - for tab
    // We need 'connected' logic too since the App request screen has a 'Connected' tab.
    $connected = [];
    // Where I am sender AND status=friend
    $cq1 = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, mp.id as friend_profile_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.receiver_id = mp.id WHERE p.sender_id='$my_profile_id' AND p.status='friend'");
    while($r = $cq1->fetch_assoc()) $connected[] = $r;
    
    // Where I am receiver AND status=friend
    $cq2 = $con->query("SELECT p.*, mp.full_name, mp.photo, mp.city, mp.education, mp.user_id, mp.id as friend_profile_id, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age FROM tbl_proposals p JOIN tbl_marriage_profiles mp ON p.sender_id = mp.id WHERE p.receiver_id='$my_profile_id' AND p.status='friend'");
    while($r = $cq2->fetch_assoc()) $connected[] = $r;

    echo json_encode(["status" => "success", "sent" => $sent, "received" => $received, "connected" => $connected]);
    exit;
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
?>
