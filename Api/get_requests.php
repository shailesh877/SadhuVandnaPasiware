<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'headers.php';
include 'connection.php';

if (!$con) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

$user_id = $_GET['user_id'] ?? '';
if(!$user_id){
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

// Get Profile ID
$pid_q = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1");
if($pid_q->num_rows == 0){
    echo json_encode(["status" => "error", "message" => "No Profile Found"]);
    exit;
}
$my_profile_id = $pid_q->fetch_assoc()['id'];

// Get Pending Requests (Received)
$query = "
SELECT 
    p.id AS proposal_id,
    mp.id AS profile_id,
    mp.full_name,
    mp.photo,
    mp.city,
    mp.age_yrs AS age
FROM tbl_proposals p
INNER JOIN tbl_marriage_profiles mp ON mp.id = p.sender_id
WHERE p.receiver_id='$my_profile_id' AND p.status='pending'
ORDER BY p.id DESC
";

$result = $con->query($query);
$requests = [];
while($row = $result->fetch_assoc()){
    $requests[] = $row;
}

echo json_encode(["status" => "success", "data" => $requests]);
?>
