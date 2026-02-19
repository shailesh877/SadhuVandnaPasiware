<?php
include("connection.php");
session_start();

// Logged-in user
// Logged-in user (MOBILE)
$user_mobile = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_mobile){
    header("Location: login.php");
    exit;
}

// Fetch current user data using MOBILE
$user = $con->query("SELECT * FROM tbl_members WHERE mobile='$user_mobile'")->fetch_assoc();

// Sanitize input
$name = $_POST['name'] ?? '';
$dob = $_POST['dob'] ?? '';
$gender = $_POST['gender'] ?? '';
$maritial_status = $_POST['maritial_status'] ?? '';
$education = $_POST['education'] ?? '';
$occupation = $_POST['occupation'] ?? '';
$mobile = $_POST['mobile'] ?? '';
$email = $_POST['email'] ?? '';
$cast = $_POST['cast'] ?? '';
$address = $_POST['address'] ?? '';
$city = $_POST['city'] ?? '';
$state = $_POST['state'] ?? '';
$hobbi = $_POST['hobbi'] ?? '';
$about = $_POST['about'] ?? '';

// ... (file upload logic omitted for brevity as it is unchanged) ...

// File upload paths
$profile_photo = $user['profile_photo'];
$cover_photo = $user['cover_photo'];

// Upload profile photo
if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0){
    $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
    $profile_photo_name = time().'_profile.'.$ext;
    if(move_uploaded_file($_FILES['profile_photo']['tmp_name'], "uploads/photo/".$profile_photo_name)){
        $profile_photo = $profile_photo_name;
        // Optionally delete old photo
        if(!empty($user['profile_photo']) && file_exists("uploads/photo/".$user['profile_photo'])){
            unlink("uploads/photo/".$user['profile_photo']);
        }
    }
}

// Upload cover photo
if(isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] == 0){
    $ext = pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION);
    $cover_photo_name = time().'_cover.'.$ext;
    if(move_uploaded_file($_FILES['cover_photo']['tmp_name'], "uploads/photo/".$cover_photo_name)){
        $cover_photo = $cover_photo_name;
        // Optionally delete old photo
        if(!empty($user['cover_photo']) && file_exists("uploads/photo/".$user['cover_photo'])){
            unlink("uploads/photo/".$user['cover_photo']);
        }
    }
}

// Update query (WHERE mobile = old mobile)
$stmt = $con->prepare("UPDATE tbl_members SET name=?, dob=?, gender=?, maritial_status=?, education=?, occupation=?, mobile=?, email=?, address=?, city=?, state=?, hobbi=?, about=?, profile_photo=?, cover_photo=?, cast=? WHERE mobile=?");
$stmt->bind_param("sssssssssssssssss", $name, $dob, $gender, $maritial_status, $education, $occupation, $mobile, $email, $address, $city, $state, $hobbi, $about, $profile_photo, $cover_photo, $cast, $user_mobile);

if($stmt->execute()){
    // Update session if mobile changed
    $_SESSION['sadhu_user_id'] = $mobile;
    $_SESSION['success'] = "Profile updated successfully!";
} else {
    $_SESSION['error'] = "Something went wrong!";
}

$stmt->close();
header("Location:profile");
exit;
