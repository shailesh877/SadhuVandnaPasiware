<?php
include("connection.php");
header('Content-Type: application/json');

// MSG91 Credentials
$authKey = "495236Ar0Le3hg86996e6d6P1";

$input = json_decode(file_get_contents("php://input"), true);

$mobile       = trim($input['mobile']       ?? $_POST['mobile']       ?? '');
$name         = trim($input['name']         ?? $_POST['name']         ?? '');
$caste        = trim($input['caste']        ?? $_POST['caste']        ?? '');
$otp          = trim($input['otp']          ?? $_POST['otp']          ?? '');

if (empty($mobile) || empty($otp)) {
    echo json_encode(["status" => "error", "message" => "missing_fields"]);
    exit;
}

// --- Verify MSG91 OTP ---
$mobileWithCode = "91" . $mobile;
$verifyUrl = "https://control.msg91.com/api/v5/otp/verify?mobile=" . urlencode($mobileWithCode) . "&otp=" . urlencode($otp) . "&authkey=" . urlencode($authKey);

$ch = curl_init($verifyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
$response = curl_exec($ch);
curl_close($ch);

$msg91Response = json_decode($response, true);

// MSG91 returns type: "success" or "error"
if (!isset($msg91Response['type']) || $msg91Response['type'] !== 'success') {
    echo json_encode([
        "status"  => "error",
        "message" => "otp_verification_failed",
        "detail"  => $msg91Response['message'] ?? 'OTP invalid'
    ]);
    exit;
}

// Ensure the name is at least formatted properly
if ($name === 'User' || empty($name)) {
    $name = 'New Member';
}

$stmt = $con->prepare("SELECT id FROM tbl_members WHERE mobile=?");
$stmt->bind_param("s", $mobile);
$stmt->execute();
$res = $stmt->get_result();
$userExists = $res->num_rows > 0;
$stmt->close();

$userData = null;
$status = "error";

if ($userExists) {
    // Already exists -> Login
    $row = $res->fetch_assoc();
    $userData = [
        "id" => $row['id'],
        "mobile" => $mobile,
        "name" => $name,
        "caste" => $caste
    ];
    $status = "success_login";
} else {
    // Generate sadhu id (just an example format)
    $sadhu_id = "SD" . rand(1000, 9999);
    $date = date('d-m-Y');

    // Make sure we have enough parameters based on your DB columns
    // We match the standard website approach: email = mobile@sadhuvandana.local etc.
    $email = $mobile . "@sadhuvandana.local"; 
    $dob = "";
    $city = "";
    $gender = "";
    $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
    $photo = ""; 
    $account_status = "Pending";

    $ins = $con->prepare("INSERT INTO tbl_members (name, email, mobile, dob, city, cast, gender, password, profile_photo, date, status, sadhu_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $ins->bind_param("ssssssssssss", $name, $email, $mobile, $dob, $city, $caste, $gender, $password, $photo, $date, $account_status, $sadhu_id);
    
    if($ins->execute()){
        $userData = [
            "id" => $con->insert_id,
            "mobile" => $mobile,
            "name" => $name,
            "caste" => $caste
        ];
        $status = "success_register";
    }
}

if ($userData !== null) {
    echo json_encode([
        "status" => $status,
        "message" => "OTP verified successfully",
        "user" => $userData
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error during register"]);
}
?>
