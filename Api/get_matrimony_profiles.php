<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'headers.php';
include 'connection.php';
include 'push_helper.php';

header('Content-Type: application/json');

if (!$con) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

$user_id = $_REQUEST['user_id'] ?? 0;
$limit   = intval($_REQUEST['limit'] ?? 20);
$offset  = intval($_REQUEST['offset'] ?? 0);
$type    = $_REQUEST['type'] ?? ''; 

// Filters
$gender    = $_REQUEST['gender'] ?? '';
$age_group = $_REQUEST['age'] ?? '';
$city      = $_REQUEST['city'] ?? '';
$education = $_REQUEST['education'] ?? '';
$search    = $_REQUEST['search'] ?? '';

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

// Helper: Get Marriage Profile ID
function getProfileId($con, $uid) {
    $q = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$uid'");
    return ($q && $q->num_rows > 0) ? $q->fetch_assoc()['id'] : 0;
}

$my_profile_id = getProfileId($con, $user_id);

// Count pending requests
$request_count = 0;
if ($my_profile_id) {
    $rq = $con->query("SELECT COUNT(*) FROM tbl_proposals WHERE receiver_id='$my_profile_id' AND status='pending'");
    $request_count = ($rq) ? $rq->fetch_row()[0] : 0;
}

$where = " WHERE 1 ";
if ($gender)    $where .= " AND gender = '$gender' ";
if ($city)      $where .= " AND city LIKE '%$city%' ";
if ($education) $where .= " AND education LIKE '%$education%' ";

if ($age_group) {
    $parts = explode('-', $age_group);
    if (count($parts) == 2) {
        $min_age = intval($parts[0]);
        $max_age = intval($parts[1]);
        // Index-friendly DOB range
        $current_date = date('Y-m-d');
        $min_dob = date('Y-m-d', strtotime("-$max_age years -1 year +1 day"));
        $max_dob = date('Y-m-d', strtotime("-$min_age years"));
        $where .= " AND dob BETWEEN '$min_dob' AND '$max_dob' ";
    }
}

if ($search) {
    $where .= " AND (full_name LIKE '%$search%' OR city LIKE '%$search%' OR caste LIKE '%$search%') ";
}

if ($my_profile_id) {
    $where .= " AND id != '$my_profile_id' ";
}

// Special case for 'connected' (used by ConnectedScreen.tsx)
if ($type === 'connected' && $my_profile_id) {
    $where .= " AND id IN (
        SELECT sender_id FROM tbl_proposals WHERE receiver_id='$my_profile_id' AND status IN ('friend', 'accepted')
        UNION
        SELECT receiver_id FROM tbl_proposals WHERE sender_id='$my_profile_id' AND status IN ('friend', 'accepted')
    )";
}

$query = "
    SELECT *, TIMESTAMPDIFF(YEAR, STR_TO_DATE(dob,'%Y-%m-%d'), CURDATE()) AS age 
    FROM tbl_marriage_profiles 
    $where 
    ORDER BY id DESC 
    LIMIT $limit OFFSET $offset
";

$res = $con->query($query);
$profiles = [];
$profile_ids = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $profiles[] = $row;
        $profile_ids[] = $row['id'];
    }
}

// Batch Query for proposal statuses
$proposal_map = [];
if (!empty($profile_ids) && $my_profile_id) {
    $ids_str = implode(',', array_map('intval', $profile_ids));
    $pq = $con->query("
        SELECT sender_id, receiver_id, status 
        FROM tbl_proposals 
        WHERE (sender_id = '$my_profile_id' AND receiver_id IN ($ids_str)) 
           OR (receiver_id = '$my_profile_id' AND sender_id IN ($ids_str))
    ");
    
    if ($pq) {
        while ($p = $pq->fetch_assoc()) {
            $other_id = ($p['sender_id'] == $my_profile_id) ? $p['receiver_id'] : $p['sender_id'];
            $status = $p['status'];
            if ($status == 'pending') {
                $status = ($p['sender_id'] == $my_profile_id) ? 'sent' : 'received';
            }
            $proposal_map[$other_id] = $status;
        }
    }
}

// Map statuses back to profiles
foreach ($profiles as &$row) {
    $row['proposal_status'] = $proposal_map[$row['id']] ?? null;
}

echo json_encode([
    "status" => "success",
    "data" => $profiles,
    "my_profile_id" => (int)$my_profile_id,
    "request_count" => (int)$request_count
]);
?>
