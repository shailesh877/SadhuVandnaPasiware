<?php
include 'headers.php';
include 'connection.php';

$user_id = $_GET['user_id'] ?? 0;

if(!$user_id){
    echo json_encode(["status" => "error", "message" => "Invalid User ID"]);
    exit;
}

// 1. Basic User Info
$stmt = $con->prepare("SELECT id, name, email, mobile, dob, city, cast, gender, profile_photo, cover_photo, about, address, maritial_status, hobbi, education, occupation FROM tbl_members WHERE id=?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if(!$user){
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}

// 2. Family Members
$family = [];
$stmt_fam = $con->prepare("SELECT * FROM tbl_family_members WHERE user_id=? ORDER BY id DESC");
$stmt_fam->bind_param("s", $user_id);
$stmt_fam->execute();
$fam_res = $stmt_fam->get_result();

while($f = $fam_res->fetch_assoc()){
    $family[] = $f;
}

// 3. Marriage Profile
$stmt_mar = $con->prepare("SELECT * FROM tbl_marriage_profiles WHERE user_id=? LIMIT 1");
$stmt_mar->bind_param("s", $user_id);
$stmt_mar->execute();
$marriage = $stmt_mar->get_result()->fetch_assoc();

// 4. Counts (Optional but useful)
// $posts_min = $con->query("SELECT COUNT(*) as count FROM tbl_posts WHERE user_id='$user_id'")->fetch_assoc()['count'];

echo json_encode([
    "status" => "success", 
    "user" => $user,
    "family" => $family,
    "marriage_profile" => $marriage
]);
?>
