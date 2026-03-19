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

// --- SUPER DEBUG ---
$total_in_mp = $con->query("SELECT COUNT(*) FROM tbl_marriage_profiles")->fetch_row()[0];
$total_in_members = $con->query("SELECT COUNT(*) FROM tbl_members")->fetch_row()[0];
$user_exists = $con->query("SELECT COUNT(*) FROM tbl_members WHERE id='$user_id'")->fetch_row()[0];
// -------------------

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
        $where .= " AND mp.dob BETWEEN '$min_dob' AND '$max_dob' ";
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

// Automatic table creation if missing
$con->query("CREATE TABLE IF NOT EXISTS tbl_blocked_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blocker_id INT NOT NULL,
    blocked_id INT NOT NULL,
    chat_platform VARCHAR(20) DEFAULT 'marriage',
    UNIQUE KEY (blocker_id, blocked_id, chat_platform)
)");

$query = "
    SELECT mp.*, 
    CASE 
        WHEN mp.dob REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE())
        WHEN mp.dob REGEXP '^[0-9]{2}-[0-9]{2}-[0-9]{4}$' THEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%d-%m-%Y'), CURDATE())
        ELSE 0 
    END AS age 
    FROM tbl_marriage_profiles mp
    LEFT JOIN tbl_members m ON mp.user_id = m.id
    $where AND (m.status != 'Blocked' OR m.status IS NULL)
    ORDER BY mp.id DESC 
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
} else {
    echo json_encode(["status" => "error", "message" => "SQL Error: " . $con->error]);
    exit;
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
    "request_count" => (int)$request_count,
    "debug" => [
        "total_profiles" => (int)$total_in_mp,
        "total_members" => (int)$total_in_members,
        "user_exists" => (int)$user_exists,
        "passed_user_id" => $user_id,
        "query" => $query
    ]
]);
?>
