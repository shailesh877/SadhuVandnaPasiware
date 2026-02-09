<?php
include 'headers.php';
include 'connection.php';

$user_id = $_GET['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

// 1. Fetch Basic Member Details
$stmt = $con->prepare("SELECT id, name, email, mobile, gender, dob, profile_photo, cover_photo, about, address, city, maritial_status, education, occupation, cast, hobbi, date as joined_date, status FROM tbl_members WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userRes = $stmt->get_result();

if ($userRes->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}

$user = $userRes->fetch_assoc();

// 2. Fetch Family Members
$family = [];
$famQ = $con->query("SELECT * FROM tbl_family_members WHERE user_id='$user_id'");
while ($f = $famQ->fetch_assoc()) {
    $family[] = $f;
}

// 3. Fetch Marriage Profile
$marriage = null;
$marQ = $con->query("SELECT * FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1");
if ($marQ->num_rows > 0) {
    $marriage = $marQ->fetch_assoc();
}

// Construct Response
$response = $user;
$response['status'] = 'success'; // Overwrite DB status 'Active'/'Blocked' with API status logic if needed, but UI uses 'status' for Marital Status usually?
// user['status'] is account status. API needs to return API status.
// Let's use 'api_status' or just 'status' = 'success'.
// App checks `res.data.status === 'success'`.
// If I allow $user fields to merge, $user['status'] might be "Active".
// I'll set $response['status'] = 'success' explicitly last to ensure it passes the check.
// But wait, if user['status'] is needed for "Blocked" check?
// I'll rename user['status'] to user['account_status'].
$response['account_status'] = $user['status'];
$response['status'] = 'success'; 

$response['family'] = $family;
$response['marriage_profile'] = $marriage;

echo json_encode($response);
?>
