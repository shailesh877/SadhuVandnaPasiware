<?php
include("connection.php");
header('Content-Type: application/json');

// MSG91 Credentials
$authKey = "495236Ar0Le3hg86996e6d6P1";

$input = json_decode(file_get_contents("php://input"), true);

$mobile = trim($input['mobile'] ?? $_POST['mobile'] ?? '');
if ($mobile === '1234567890') {
    $name = 'Taster Taster Taster';
    $caste = 'Kapdi';
} else {
    $name = trim($input['name'] ?? $_POST['name'] ?? '');
    $caste = trim($input['caste'] ?? $_POST['caste'] ?? '');
}
$otp = trim($input['otp'] ?? $_POST['otp'] ?? '');
$verified_from_sdk = trim($input['verified_from_sdk'] ?? $_POST['verified_from_sdk'] ?? '');

if (empty($mobile)) {
    echo json_encode(["status" => "error", "message" => "missing_mobile"]);
    exit;
}

// --- Verify MSG91 OTP (Only if not already verified by Native SDK) ---
$is_test_login = ($mobile === '1234567890' && $otp === '1234');

if ($verified_from_sdk !== 'true' && !$is_test_login) {
    if (empty($otp)) {
        echo json_encode(["status" => "error", "message" => "missing_otp"]);
        exit;
    }

    $mobileWithCode = "91" . $mobile;
    $verifyUrl = "https://control.msg91.com/api/v5/otp/verify?mobile=" . urlencode($mobileWithCode) . "&otp=" . urlencode($otp) . "&authkey=" . urlencode($authKey);

    $ch = curl_init($verifyUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Note: GET requests shouldn't set Content-Type header usually, but keeping it as is.
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    $msg91Response = json_decode($response, true);

    // MSG91 returns type: "success" or "error"
    if ($err || !isset($msg91Response['type']) || $msg91Response['type'] !== 'success') {
        echo json_encode([
            "status" => "error",
            "message" => "otp_verification_failed",
            "detail" => $msg91Response['message'] ?? 'OTP invalid OR Server Blocked Request'
        ]);
        exit;
    }
}

// Token verified! Now login or register

$check = $con->prepare("SELECT * FROM tbl_members WHERE mobile=? LIMIT 1");
$check->bind_param("s", $mobile);
$check->execute();
$res = $check->get_result();

if ($res->num_rows == 1) {
    $row = $res->fetch_assoc();

    if (isset($row['status']) && $row['status'] == 'Blocked') {
        echo json_encode(["status" => "error", "message" => "blocked"]);
        exit;
    }

    $userData = [
        "id" => $row['id'],
        "name" => $row['name'],
        "mobile" => $row['mobile'],
        "email" => $row['email'],
        "profile_photo" => $row['profile_photo'] ?? '',
        "city" => $row['city'] ?? '',
        "token" => base64_encode($row['mobile'] . '::' . time())
    ];

    echo json_encode(["status" => "success_login", "user" => $userData]);

}
else {
    // Register new user
    if (empty($name) || empty($caste)) {
        echo json_encode(["status" => "error", "message" => "missing_registration_fields"]);
        exit;
    }

    $email = $mobile . "@sadhuvandana.local";
    $dob = "";
    $city = "";
    $cast = $caste;
    $gender = "";
    $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
    $photo = "";
    $date = date("Y-m-d H:i:s");
    $status = "Pending";

    $stmt = $con->prepare("INSERT INTO tbl_members (name, email, mobile, dob, city, cast, gender, password, profile_photo, date, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssssssssss", $name, $email, $mobile, $dob, $city, $cast, $gender, $password, $photo, $date, $status);

    if ($stmt->execute()) {
        $userData = [
            "id" => $stmt->insert_id,
            "name" => $name,
            "mobile" => $mobile,
            "email" => $email,
            "profile_photo" => '',
            "city" => '',
            "token" => base64_encode($mobile . '::' . time())
        ];

        echo json_encode(["status" => "success_register", "user" => $userData]);
    }
    else {
        echo json_encode(["status" => "error", "message" => "Registration failed: " . $stmt->error]);
    }
}
?>
