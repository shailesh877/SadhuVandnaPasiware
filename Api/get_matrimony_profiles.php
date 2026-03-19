<?php
header('Content-Type: application/json');
include("connection.php");

// Mobile params
$user_id = $_REQUEST['user_id'] ?? 0; // Numeric member ID from app
$limit   = intval($_REQUEST['limit'] ?? 20);
$offset  = intval($_REQUEST['offset'] ?? 0);
$gender    = $_REQUEST['gender'] ?? '';
$age_group = $_REQUEST['age'] ?? '';
$city      = trim($_REQUEST['city'] ?? '');
$education = trim($_REQUEST['education'] ?? '');
$search    = $_REQUEST['search'] ?? '';

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

// 1. Get Current User's Marriage Profile ID (Website Logic)
$my_profile_q = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1");
$my_profile = $my_profile_q->fetch_assoc();
$my_profile_id = $my_profile['id'] ?? 0;

// 2. Base Query (Exactly as website's fetch_profiles.php)
$query = "
SELECT mp.*, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age
FROM tbl_marriage_profiles mp
JOIN tbl_members m ON m.id = mp.user_id
WHERE m.status != 'Blocked' AND mp.id != '$my_profile_id'
";

// 3. Apply Filters (Exactly as website)
if($gender) $query .= " AND mp.gender='$gender'";
if($city) $query .= " AND mp.city LIKE '%$city%'";
if($education) $query .= " AND mp.education LIKE '%$education%'";

if($age_group){
    $range = explode('-',$age_group);
    if(count($range)==2){
        $min = (int)$range[0];
        $max = (int)$range[1];
        $query .= " AND TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) BETWEEN $min AND $max";
    }
}

// Search (App specific enhancement)
if($search){
    $query .= " AND (mp.full_name LIKE '%$search%' OR mp.city LIKE '%$search%' OR mp.caste LIKE '%$search%')";
}

// Order and Pagination
$query .= " ORDER BY mp.id DESC LIMIT $limit OFFSET $offset";

$result = $con->query($query);
$profiles = [];
$profile_ids = [];

if($result){
    while($row = $result->fetch_assoc()){
        // Proposal check per row (Consistent with website logic)
        $proposal_status = 'none';
        $proposal_check = $con->query("
            SELECT status, sender_id 
            FROM tbl_proposals
            WHERE (sender_id='$my_profile_id' AND receiver_id='".$row['id']."')
               OR (sender_id='".$row['id']."' AND receiver_id='$my_profile_id')
            ORDER BY id DESC LIMIT 1
        ");

        if($proposal_check && $proposal_check->num_rows > 0){
            $p = $proposal_check->fetch_assoc();
            $status = strtolower($p['status']);
            if($status == 'pending'){
                $proposal_status = ($p['sender_id'] == $my_profile_id) ? 'sent' : 'received';
            } else {
                $proposal_status = $status;
            }
        }
        
        $row['proposal_status'] = $proposal_status;
        $profiles[] = $row;
    }
}

echo json_encode([
    "status" => "success",
    "data" => $profiles,
    "my_profile_id" => $my_profile_id,
    "request_count" => 0 // Optional: count requests if needed
]);
?>
