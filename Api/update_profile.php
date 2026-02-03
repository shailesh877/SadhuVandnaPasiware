<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'headers.php';
include 'connection.php';

if (!$con) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}
session_start();

// Logged-in user

$user_id = $_POST['user_id'] ?? 0;
if($user_id <= 0) {
    if(!empty($user_email)){
       $u = $con->query("SELECT id FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
       $user_id = $u['id'];
    }
}

if($user_id <= 0){
     echo json_encode(["status" => "error", "message" => "Unauthorized"]);
     exit;
}

// Fetch current user data by ID
$user = $con->query("SELECT * FROM tbl_members WHERE id='$user_id'")->fetch_assoc();
if(!$user){
     echo json_encode(["status" => "error", "message" => "User not found"]);
     exit;
}

// Sanitize input
$name = $_POST['name'] ?? $user['name'];
$dob = $_POST['dob'] ?? $user['dob'];
$gender = $_POST['gender'] ?? $user['gender'];
$maritial_status = $_POST['maritial_status'] ?? $user['maritial_status'];
$education = $_POST['education'] ?? $user['education'];
$occupation = $_POST['occupation'] ?? $user['occupation'];
$mobile = $_POST['mobile'] ?? $user['mobile'];
// $email = $_POST['email'] ?? $user['email']; // Email usually shouldn't change
$cast = $_POST['cast'] ?? $user['cast'];
$address = $_POST['address'] ?? $user['address'];
$city = $_POST['city'] ?? $user['city'];
$state = $_POST['state'] ?? $user['state'];
$hobbi = $_POST['hobbi'] ?? $user['hobbi'];
$about = $_POST['about'] ?? $user['about'];

// File upload paths
$profile_photo = $user['profile_photo'];
$cover_photo = $user['cover_photo'];

// Upload profile photo
if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0){ // App sends 'photo'
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $profile_photo_name = time().'_profile.'.$ext;
    if(move_uploaded_file($_FILES['photo']['tmp_name'], "../uploads/photo/".$profile_photo_name)){
        $profile_photo = $profile_photo_name;
    }
}
// Website uses 'profile_photo', app might send that too? Handle both
if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0){
    $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
    $profile_photo_name = time().'_profile.'.$ext;
    if(move_uploaded_file($_FILES['profile_photo']['tmp_name'], "../uploads/photo/".$profile_photo_name)){
        $profile_photo = $profile_photo_name;
    }
}

// Upload Cover Photo
if(isset($_FILES['cover']) && $_FILES['cover']['error'] == 0){
    $ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
    $cover_photo_name = time().'_cover.'.$ext;
    // Assuming cover photos go to same dir or separate? Website usually uses same or 'cover'. 
    // Let's use 'photo' dir as per code context or check if 'uploads/cover' exists?
    // User profile screen uses PHOTO_URL for cover. So it expects it in 'uploads/photo/'? 
    // Checking PublicProfileScreen: getImageUrl(user.cover_photo, PHOTO_URL). YES.
    if(move_uploaded_file($_FILES['cover']['tmp_name'], "../uploads/photo/".$cover_photo_name)){
        $cover_photo = $cover_photo_name;
    }
}


// Update query
$stmt = $con->prepare("UPDATE tbl_members SET name=?, dob=?, gender=?, maritial_status=?, education=?, occupation=?, mobile=?, address=?, city=?, state=?, hobbi=?, about=?, profile_photo=?, cover_photo=?, cast=? WHERE id=?");
$stmt->bind_param("sssssssssssssssi", $name, $dob, $gender, $maritial_status, $education, $occupation, $mobile, $address, $city, $state, $hobbi, $about, $profile_photo, $cover_photo, $cast, $user_id);

if($stmt->execute()){
    // Return updated user object
     $updatedUser = $con->query("SELECT * FROM tbl_members WHERE id='$user_id'")->fetch_assoc();
     unset($updatedUser['password']); // Don't send password back
    echo json_encode(["status" => "success", "message" => "Profile updated", "user" => $updatedUser]);
} else {
    echo json_encode(["status" => "error", "message" => "Update failed: " . $con->error]);
}
?>
