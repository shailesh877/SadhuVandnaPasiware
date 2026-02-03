<?php
include 'headers.php';
include 'connection.php';

$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? '';
$gender = $_POST['gender'] ?? $_GET['gender'] ?? '';
$ageRange = $_POST['age'] ?? $_GET['age'] ?? '';
$city = trim($_POST['city'] ?? $_GET['city'] ?? '');
$education = trim($_POST['education'] ?? $_GET['education'] ?? '');

$my_profile_id = 0;
if($user_id){
    $stmt = $con->prepare("SELECT id FROM tbl_marriage_profiles WHERE user_id=? LIMIT 1");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows > 0){
        $my_profile_id = $res->fetch_assoc()['id'];
    }
}

// Request Count
$requestCount = 0;
if($my_profile_id){
    $rc = $con->query("SELECT COUNT(*) AS total FROM tbl_proposals WHERE receiver_id = '$my_profile_id' AND status = 'pending'");
    $requestCount = $rc->fetch_assoc()['total'];
}


// Base Query
$query = "SELECT mp.*, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age
          FROM tbl_marriage_profiles mp
          JOIN tbl_members m ON m.id = mp.user_id
          WHERE m.status != 'Blocked'";

if($my_profile_id){
    $query .= " AND mp.id != '$my_profile_id'";
}

if($gender) $query .= " AND mp.gender='$gender'";
if($city) $query .= " AND mp.city LIKE '%$city%'";
if($education) $query .= " AND mp.education LIKE '%$education%'";
if($ageRange){
    $range = explode('-',$ageRange);
    if(count($range)==2){
        $min = (int)$range[0];
        $max = (int)$range[1];
        $query .= " AND TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) BETWEEN $min AND $max";
    }
}

$query .= " ORDER BY mp.id DESC";

$result = $con->query($query);

$profiles = [];
if($result){
    while($row = $result->fetch_assoc()){
        // Proposal Status
        $proposal_status = null;
        $is_sender = false;
        
        if($my_profile_id){
            $chk = $con->query("SELECT status, sender_id FROM tbl_proposals 
                WHERE (sender_id='$my_profile_id' AND receiver_id='".$row['id']."')
                   OR (sender_id='".$row['id']."' AND receiver_id='$my_profile_id')
                ORDER BY id DESC LIMIT 1");
            if($chk->num_rows > 0){
                $p = $chk->fetch_assoc();
                $proposal_status = strtolower($p['status']);
                $is_sender = ($p['sender_id'] == $my_profile_id);
            }
        }
        
        $row['proposal_status'] = $proposal_status;
        $row['is_sender'] = $is_sender;
        $profiles[] = $row;
    }
}

echo json_encode([
    "status" => "success", 
    "data" => $profiles, 
    "request_count" => $requestCount,
    "my_profile_id" => $my_profile_id
]);
?>
