<?php
include 'headers.php';
include 'connection.php';

$user_id = $_GET['user_id'] ?? 0;

if(!$user_id){
    echo json_encode(["status" => "error", "message" => "Invalid User ID"]);
    exit;
}

// 1. Basic User Info (including business flags)
$stmt = $con->prepare("SELECT id, name, email, mobile, dob, city, cast, gender, profile_photo, cover_photo, about, address, maritial_status, hobbi, education, occupation, is_business, parent_userid, category FROM tbl_members WHERE id=?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if(!$user){
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}

// 2. Business Details (if it's a business page)
$business_details = null;
if($user['is_business'] == 1) {
    $stmt_biz = $con->prepare("SELECT * FROM tbl_business_details WHERE user_id=? LIMIT 1");
    $stmt_biz->bind_param("i", $user_id);
    $stmt_biz->execute();
    $business_details = $stmt_biz->get_result()->fetch_assoc();
}

// 3. Family Members (only for personal accounts)
$family = [];
if($user['is_business'] != 1) {
    $stmt_fam = $con->prepare("SELECT * FROM tbl_family_members WHERE user_id=? ORDER BY id DESC");
    $stmt_fam->bind_param("s", $user_id);
    $stmt_fam->execute();
    $fam_res = $stmt_fam->get_result();
    while($f = $fam_res->fetch_assoc()){
        $family[] = $f;
    }
}

// 4. Marriage Profile (only for personal accounts)
$marriage = null;
if($user['is_business'] != 1) {
    $stmt_mar = $con->prepare("SELECT * FROM tbl_marriage_profiles WHERE user_id=? LIMIT 1");
    $stmt_mar->bind_param("s", $user_id);
    $stmt_mar->execute();
    $marriage = $stmt_mar->get_result()->fetch_assoc();
}

echo json_encode([
    "status" => "success", 
    "user" => $user,
    "business_details" => $business_details,
    "family" => $family,
    "marriage_profile" => $marriage
]);
?>
