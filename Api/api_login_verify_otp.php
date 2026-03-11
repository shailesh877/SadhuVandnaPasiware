<?php
include("connection.php");
header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);
$mobile       = trim($input['mobile']       ?? $_POST['mobile']       ?? '');
$name         = trim($input['name']         ?? $_POST['name']         ?? '');
$caste        = trim($input['caste']        ?? $_POST['caste']        ?? '');
$widget_token = trim($input['access_token'] ?? $_POST['access_token'] ?? '');

if (empty($mobile) || empty($widget_token)) {
    echo json_encode(["status" => "error", "message" => "missing_fields"]);
    exit;
}

$authKey = "495236Ar0Le3hg86996e6d6P1"; 
$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => 'https://control.msg91.com/api/v5/widget/verifyAccessToken',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_POSTFIELDS => json_encode(["authkey" => $authKey, "access-token" => $widget_token]),
]);
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

$json = json_decode($response, true);
if ($err || !isset($json['type']) || $json['type'] !== 'success') {
     echo json_encode(["status" => "error", "message" => "invalid_widget_verification"]);
     exit;
}

if ($name === 'User' || empty($name)) $name = 'New Member';

$stmt = $con->prepare("SELECT id FROM tbl_members WHERE mobile=?");
$stmt->bind_param("s", $mobile);
$stmt->execute();
$res = $stmt->get_result();

$userData = null;
$status = "error";

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $userData = ["id" => $row['id'], "mobile" => $mobile, "name" => $name, "caste" => $caste];
    $status = "success_login";
} else {
    $sadhu_id = "SD" . rand(1000, 9999);
    $date = date('d-m-Y');
    $email = $mobile . "@sadhuvandana.local"; 
    $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
    $account_status = "Pending";

    $ins = $con->prepare("INSERT INTO tbl_members (name, email, mobile, cast, password, date, status, sadhu_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if($ins){
        $ins->bind_param("ssssssss", $name, $email, $mobile, $caste, $password, $date, $account_status, $sadhu_id);
        if($ins->execute()){
            $userData = ["id" => $con->insert_id, "mobile" => $mobile, "name" => $name, "caste" => $caste];
            $status = "success_register";
        }
    }
}

echo json_encode(["status" => $status, "message" => "OTP verified successfully", "user" => $userData]);
?>
