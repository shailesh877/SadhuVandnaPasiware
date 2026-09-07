<?php
include("connection.php");
header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);

// Accept token/mobile from JSON body or POST form data
$mobile = trim($input['mobile'] ?? $_POST['mobile'] ?? '');
$token = trim($input['token'] ?? $_POST['token'] ?? '');

if (empty($mobile)) {
    echo json_encode([
        "status" => "error",
        "message" => "not_logged_in"
    ]);
    exit;
}

$stmt = $con->prepare("SELECT id, name, mobile, email, status, profile_photo, city FROM tbl_members WHERE mobile=? LIMIT 1");
$stmt->bind_param("s", $mobile);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows != 1) {
    echo json_encode([
        "status" => "error",
        "message" => "user_not_found"
    ]);
    exit;
}

$row = $res->fetch_assoc();

/* BLOCK CHECK */
if ($row['status'] == "Blocked") {
    echo json_encode([
        "status" => "error",
        "message" => "account_blocked"
    ]);
    exit;
}

/* UPDATE LAST ACTIVE (Commented out if column last_active doesn't exist yet, uncomment if it does) */
// @$con->query("UPDATE tbl_members SET last_active=NOW() WHERE mobile='$mobile'");

$userData = [
    "id" => $row['id'],
    "name" => $row['name'],
    "mobile" => $row['mobile'],
    "email" => $row['email'],
    "city" => $row['city'] ?? '',
    "profile_photo" => $row['profile_photo'] ?? '',
    "token" => base64_encode($row['mobile'] . '::' . time()) // Refresh token if needed
];

echo json_encode([
    "status" => "success",
    "user" => $userData
]);
?>
