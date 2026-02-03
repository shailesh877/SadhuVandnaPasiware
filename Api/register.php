<?php
include 'headers.php';
include 'connection.php';

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$mobile = $_POST['mobile'] ?? '';
$password = $_POST['password'] ?? '';

// Optional fields with defaults (as per original logic placeholders)
$dob = $_POST['dob'] ?? '01-01-2000'; 
$city = $_POST['city'] ?? '';
$cast = $_POST['cast'] ?? '';
$gender = $_POST['gender'] ?? 'Male';

if (!$name || !$email || !$mobile || !$password) {
    echo json_encode(["status" => "error", "message" => "All fields required"]);
    exit;
}

// Check duplicates
$check = $con->query("SELECT * FROM tbl_members WHERE email='$email' OR mobile='$mobile'");
if ($check->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email or Mobile already registered"]);
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Insert
$stmt = $con->prepare("INSERT INTO tbl_members (name, email, mobile, password, dob, city, cast, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssss", $name, $email, $mobile, $hashed_password, $dob, $city, $cast, $gender);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Registration Successful"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to register"]);
}
?>
