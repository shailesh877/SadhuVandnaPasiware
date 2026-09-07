<?php
include 'headers.php';
include 'connection.php';

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$mobile = $_POST['mobile'] ?? '';
$password = $_POST['password'] ?? '';
$dob = $_POST['dob'] ?? date('d-m-Y'); 
$city = $_POST['city'] ?? '';
$cast = $_POST['cast'] ?? '';
$gender = $_POST['gender'] ?? '';

if(empty($name) || empty($email) || empty($mobile) || empty($password)){
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}

// Check if user exists
$check = $con->prepare("SELECT id FROM tbl_members WHERE email=? OR mobile=?");
$check->bind_param("ss", $email, $mobile);
$check->execute();
$res = $check->get_result();

if($res->num_rows > 0){
    echo json_encode(["status" => "error", "message" => "Email or Mobile already exists"]);
    exit;
}

// Register
$hashed = password_hash($password, PASSWORD_BCRYPT);
$date = date('d-m-Y H:i:s');

$stmt = $con->prepare("INSERT INTO tbl_members (name, email, mobile, dob, city, cast, gender, password, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssss", $name, $email, $mobile, $dob, $city, $cast, $gender, $hashed, $date);

if($stmt->execute()){
    echo json_encode(["status" => "success", "message" => "Registration Successful"]);
} else {
    echo json_encode(["status" => "error", "message" => "Registration Failed"]);
}
?>
