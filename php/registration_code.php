<?php
include("connection.php");
session_start();

// Get form data safely
$name     = trim($_POST['name']);
$email    = trim($_POST['email']);
$mobile   = trim($_POST['phone']);
$dob      = trim($_POST['dob']);
$city     = trim($_POST['city']);
$cast     = trim($_POST['cast']);
$gender   = trim($_POST['gender']);
$password = trim($_POST['password']);
$confirm_password = trim($_POST['confirm_password']);
date_default_timezone_set('Asia/Kolkata');
$date = date('d-m-Y H:i:s');
// Basic validation
if (empty($name) || empty($email) || empty($mobile) || empty($dob) || empty($city) || empty($gender) || empty($password)) {
    echo "<script>alert('All fields are required!'); window.history.back();</script>";
    exit;
}

// Password match check
if ($password !== $confirm_password) {
    echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
    exit;
}

// Email format validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Invalid email format!'); window.history.back();</script>";
    exit;
}

// Validate DOB (basic, must be a valid date in past)
if (!strtotime($dob) || strtotime($dob) > time()) {
    echo "<script>alert('Invalid Date of Birth!'); window.history.back();</script>";
    exit;
}

// Hash password securely
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// === Duplicate Email / Mobile Check ===
$check = $con->prepare("SELECT id FROM tbl_members WHERE email = ? OR mobile = ?");
$check->bind_param("ss", $email, $mobile);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "<script>alert('Email or Mobile number already registered!'); window.history.back();</script>";
    exit;
}

// === Insert Data ===
$stmt = $con->prepare("INSERT INTO tbl_members (name, email, mobile, dob, city,cast, gender, password,date) VALUES (?, ?, ?, ?, ?, ?, ?,?,?)");
$stmt->bind_param("sssssssss", $name, $email, $mobile, $dob, $city,$cast, $gender, $hashedPassword,$date);

if ($stmt->execute()) {
    echo "<script>
        alert('🎉 Registration successful! You can now log in.');
        window.location.href='login.php';
    </script>";
} else {
    echo "<script>
        alert('Something went wrong while registering. Try again!');
        window.history.back();
    </script>";
}

$stmt->close();
$con->close();
?>
