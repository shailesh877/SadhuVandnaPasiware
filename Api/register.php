<?php
include 'headers.php';
include 'connection.php';

$data = json_decode(file_get_contents("php://input"), true);

$name = $data['name'] ?? $_POST['name'] ?? '';
$email = $data['email'] ?? $_POST['email'] ?? '';
$mobile = $data['phone'] ?? $_POST['phone'] ?? '';
$dob = $data['dob'] ?? $_POST['dob'] ?? '';
$city = $data['city'] ?? $_POST['city'] ?? '';
$cast = $data['cast'] ?? $_POST['cast'] ?? '';
$gender = $data['gender'] ?? $_POST['gender'] ?? '';
$password = $data['password'] ?? $_POST['password'] ?? '';

if (!$name || !$email || !$mobile || !$password) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

// Check duplicates
$check = $con->prepare("SELECT id FROM tbl_members WHERE email=? OR mobile=?");
$check->bind_param("ss", $email, $mobile);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email or Mobile already registered"]);
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
date_default_timezone_set('Asia/Kolkata');
$date = date('d-m-Y H:i:s');

$stmt = $con->prepare("INSERT INTO tbl_members (name, email, mobile, dob, city, cast, gender, password, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssss", $name, $email, $mobile, $dob, $city, $cast, $gender, $hashedPassword, $date);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Registration successful"]);
} else {
    echo json_encode(["status" => "error", "message" => "Registration failed"]);
}
?>
