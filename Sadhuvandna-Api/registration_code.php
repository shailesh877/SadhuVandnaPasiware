<?php
include("connection.php");
session_start();

date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');

/* ================== GET FORM DATA ================== */
$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$mobile   = trim($_POST['phone'] ?? '');
$dob      = trim($_POST['dob'] ?? '');
$city     = trim($_POST['city'] ?? '');
$cast     = trim($_POST['cast'] ?? '');
$gender   = trim($_POST['gender'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

/* ================== BASIC VALIDATION ================== */
if (
    empty($name) || empty($email) || empty($mobile) || empty($dob) ||
    empty($city) || empty($cast) || empty($gender) ||
    empty($password) || empty($confirm_password)
) {
    echo "<script>alert('All fields are required!'); window.history.back();</script>";
    exit;
}

/* ================== PHOTO VALIDATION ================== */
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== 0) {
    echo "<script>alert('Profile photo is required!'); window.history.back();</script>";
    exit;
}

$allowedTypes = ['image/jpeg','image/png','image/jpg','image/webp'];
$photoType = $_FILES['photo']['type'];

if (!in_array($photoType, $allowedTypes)) {
    echo "<script>alert('Only JPG, PNG or WEBP images are allowed!'); window.history.back();</script>";
    exit;
}

/* ================== PASSWORD CHECK ================== */
if ($password !== $confirm_password) {
    echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
    exit;
}

/* ================== EMAIL VALIDATION ================== */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Invalid email format!'); window.history.back();</script>";
    exit;
}

/* ================== DOB VALIDATION ================== */
if (!strtotime($dob) || strtotime($dob) > time()) {
    echo "<script>alert('Invalid Date of Birth!'); window.history.back();</script>";
    exit;
}

/* ================== DUPLICATE CHECK ================== */
$check = $con->prepare("SELECT id FROM tbl_members WHERE email = ? OR mobile = ?");
$check->bind_param("ss", $email, $mobile);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "<script>alert('Email or Mobile already registered!'); window.history.back();</script>";
    exit;
}

/* ================== PHOTO UPLOAD ================== */
$uploadDir = "uploads/photo/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
$photoName = "profile_" . time() . "_" . rand(1000,9999) . "." . $ext;
$photoPath = $uploadDir . $photoName;

if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
    echo "<script>alert('Failed to upload profile photo!'); window.history.back();</script>";
    exit;
}

/* ================== HASH PASSWORD ================== */
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

/* ================== INSERT DATA ================== */
$stmt = $con->prepare("
INSERT INTO tbl_members
(name, email, mobile, dob, city, cast, gender, password, profile_photo, date)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssssssss",
    $name, $email, $mobile, $dob, $city, $cast,
    $gender, $hashedPassword, $photoName, $date
);

if ($stmt->execute()) {
    echo "<script>
        alert('🎉 Registration successful! You can now log in.');
        window.location.href='login.php';
    </script>";
} else {
    echo "<script>
        alert('Something went wrong while registering!');
        window.history.back();
    </script>";
}

$stmt->close();
$con->close();
?>