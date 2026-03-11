<?php
include("connection.php");
header('Content-Type: application/json');

// MSG91 Credentials
$authKey = "495236Ar0Le3hg86996e6d6P1";

// Get JSON or POST input
$input = json_decode(file_get_contents("php://input"), true);
$mobile = trim($input['mobile'] ?? $_POST['mobile'] ?? '');
$name   = trim($input['name']   ?? $_POST['name']   ?? '');
$caste  = trim($input['caste']  ?? $_POST['caste']  ?? '');

if (empty($mobile) || empty($name) || empty($caste)) {
    echo json_encode(["status" => "error", "message" => "missing_fields"]);
    exit;
}

$allowed_castes = [
    "Kapdi", "Deshani", "Dudhrejia", "Danidhariya", "Gondaliya", "Mesvaniya",
    "Ramkabir", "Ramsnehi", "Vaghani", "Chapbai", "Parabiya", "Hariyani",
    "Sarpadadiya", "Ramdevputra", "Ravibhan", "Baroliya"
];

if (!in_array($caste, $allowed_castes)) {
    echo json_encode(["status" => "error", "message" => "invalid_caste"]);
    exit;
}

if (!preg_match('/^[0-9]{10}$/', $mobile)) {
    echo json_encode(["status" => "error", "message" => "invalid_mobile"]);
    exit;
}

// Daily limit check
date_default_timezone_set('Asia/Kolkata');
$today = date('Y-m-d');

// Make sure table is created
@$con->query("CREATE TABLE IF NOT EXISTS tbl_otp_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mobile VARCHAR(15) NOT NULL,
    otp VARCHAR(10) NULL,
    sent_time DATETIME NOT NULL
)");
@$con->query("ALTER TABLE tbl_otp_attempts ADD COLUMN otp VARCHAR(10) NULL");

$stmt = $con->prepare("SELECT COUNT(*) FROM tbl_otp_attempts WHERE mobile=? AND DATE(sent_time)=?");
$stmt->bind_param("ss", $mobile, $today);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count >= 10) {
    echo json_encode(["status" => "error", "message" => "limit_exceeded"]);
    exit;
}

// --- Send OTP via MSG91 ---
$mobileWithCode = "91" . $mobile; // Add India country code
$templateId = "67d02447d6ba711b7d549d42"; // Hardcoded MSG91 Template ID for OTP

$url = "https://control.msg91.com/api/v5/otp?mobile=" . urlencode($mobileWithCode) . "&template_id=" . urlencode($templateId) . "&authkey=" . urlencode($authKey) . "&otp_expiry=10";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$msg91Response = json_decode($response, true);

// MSG91 returns type: "success" on success
if (!isset($msg91Response['type']) || $msg91Response['type'] !== 'success') {
    echo json_encode([
        "status"  => "error",
        "message" => "otp_send_failed",
        "detail"  => $msg91Response['message'] ?? 'Unknown Error'
    ]);
    exit;
}

// Log the attempt
$now = date('Y-m-d H:i:s');
$ins = $con->prepare("INSERT INTO tbl_otp_attempts (mobile, otp, sent_time) VALUES (?, 'msg91', ?)");
$ins->bind_param("ss", $mobile, $now);
$ins->execute();

echo json_encode([
    "status"  => "success",
    "message" => "otp_sent"
]);
?>
