<?php
include 'headers.php';
include 'connection.php';

// Fetch profiles (basic list)
// Filters can be added via POST params later
$query = "SELECT * FROM tbl_marriage_profiles WHERE status != 'Blocked' ORDER BY id DESC";
$result = $con->query($query);

$profiles = [];
while($row = $result->fetch_assoc()){
    // Calculate Age
    $age = 'N/A';
    if(!empty($row['dob'])){
        $dob = new DateTime($row['dob']);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
    }
    $row['age'] = $age;
    $profiles[] = $row;
}

echo json_encode(["status" => "success", "data" => $profiles]);
?>
