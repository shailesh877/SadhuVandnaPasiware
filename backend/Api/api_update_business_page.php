<?php
include 'headers.php';
include 'connection.php';

// Support both JSON payload and multipart/form-data
$data = json_decode(file_get_contents("php://input"), true);

$user_id       = $data['user_id'] ?? $_POST['user_id'] ?? '';
$businessname  = $data['businessname'] ?? $_POST['businessname'] ?? '';
$category      = $data['category'] ?? $_POST['category'] ?? '';
$phone_no      = $data['phone_no'] ?? $_POST['phone_no'] ?? '';
$email         = $data['email'] ?? $_POST['email'] ?? '';
$website_url   = $data['website_url'] ?? $_POST['website_url'] ?? '';
$social_media_account = $data['social_media_account'] ?? $_POST['social_media_account'] ?? '';
$country       = $data['country'] ?? $_POST['country'] ?? '';
$state         = $data['state'] ?? $_POST['state'] ?? '';
$district      = $data['district'] ?? $_POST['district'] ?? '';
$full_address  = $data['full_address'] ?? $_POST['full_address'] ?? '';
$pincode       = $data['pincode'] ?? $_POST['pincode'] ?? '';
$opening_hours = $data['opening_hours'] ?? $_POST['opening_hours'] ?? '';

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "user_id required"]);
    exit;
}

// Verify this is actually a business account
$check = $con->prepare("SELECT id, profile_photo, cover_photo FROM tbl_members WHERE id=? AND is_business=1");
$check->bind_param("i", $user_id);
$check->execute();
$result = $check->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Not a valid business account"]);
    exit;
}

$user = $result->fetch_assoc();
$profile_photo = $user['profile_photo'];
$cover_photo = $user['cover_photo'];

// Upload profile photo
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
    $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
    $profile_photo_name = time().'_profile.'.$ext;
    if(move_uploaded_file($_FILES['profile_photo']['tmp_name'], "../uploads/photo/".$profile_photo_name)){
        $profile_photo = $profile_photo_name;
    }
}

// Upload cover photo
if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] == 0) {
    $ext = pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION);
    $cover_photo_name = time().'_cover.'.$ext;
    if(move_uploaded_file($_FILES['cover_photo']['tmp_name'], "../uploads/photo/".$cover_photo_name)){
        $cover_photo = $cover_photo_name;
    }
}

// Update tbl_members name, category and photos
$stmt = $con->prepare("UPDATE tbl_members SET name=?, category=?, profile_photo=?, cover_photo=? WHERE id=?");
$stmt->bind_param("ssssi", $businessname, $category, $profile_photo, $cover_photo, $user_id);
$stmt->execute();


// Upsert tbl_business_details
$existCheck = $con->prepare("SELECT id FROM tbl_business_details WHERE user_id=?");
$existCheck->bind_param("i", $user_id);
$existCheck->execute();
$existing = $existCheck->get_result()->fetch_assoc();

if ($existing) {
    $upd = $con->prepare("UPDATE tbl_business_details SET phone_no=?, email=?, website_url=?, social_media_account=?, country=?, state=?, district=?, full_address=?, pincode=?, opening_hours=? WHERE user_id=?");
    $upd->bind_param("ssssssssssi", $phone_no, $email, $website_url, $social_media_account, $country, $state, $district, $full_address, $pincode, $opening_hours, $user_id);
    $upd->execute();
} else {
    $ins = $con->prepare("INSERT INTO tbl_business_details (user_id, phone_no, email, website_url, social_media_account, country, state, district, full_address, pincode, opening_hours) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $ins->bind_param("issssssssss", $user_id, $phone_no, $email, $website_url, $social_media_account, $country, $state, $district, $full_address, $pincode, $opening_hours);
    $ins->execute();
}

// Fetch fresh user data
$freshRes = $con->query("SELECT * FROM tbl_members WHERE id='$user_id'");
$freshData = $freshRes->fetch_assoc();

echo json_encode(["status" => "success", "message" => "Business profile updated successfully", "data" => $freshData]);
?>
