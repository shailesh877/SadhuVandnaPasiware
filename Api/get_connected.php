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

// Get Profile ID of logged in user
$pid_q = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1");
if($pid_q->num_rows == 0){
    echo json_encode(["status" => "error", "message" => "No Profile Found"]);
    exit;
}
$my_profile_id = $pid_q->fetch_assoc()['id'];

// Get Friends
$query = "
SELECT 
    p.id AS proposal_id,
    mp.id AS profile_id,
    mp.full_name,
    mp.photo,
    mp.city,
    mp.user_id
FROM tbl_proposals p
INNER JOIN tbl_marriage_profiles mp 
    ON mp.id = IF(p.sender_id='$my_profile_id', p.receiver_id, p.sender_id)
WHERE (p.sender_id='$my_profile_id' OR p.receiver_id='$my_profile_id')
AND p.status='friend'
AND EXISTS (
    SELECT 1 FROM tbl_messages m 
    WHERE (m.sender_id = mp.id AND m.receiver_id = '$my_profile_id') 
       OR (m.sender_id = '$my_profile_id' AND m.receiver_id = mp.id)
)
ORDER BY (
    SELECT MAX(created_at) FROM tbl_messages m2 
    WHERE (m2.sender_id = mp.id AND m2.receiver_id = '$my_profile_id') 
       OR (m2.sender_id = '$my_profile_id' AND m2.receiver_id = mp.id)
) DESC
";

$result = $con->query($query);
$friends = [];
while($row = $result->fetch_assoc()){
    $friends[] = $row;
}

echo json_encode(["status" => "success", "data" => $friends]);
?>
