<?php
include 'headers.php';
include 'connection.php';

$user_id = $_POST['user_id'] ?? '';

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

date_default_timezone_set("Asia/Kolkata");

// Check if profile exists
$check = $con->query("SELECT * FROM tbl_marriage_profiles WHERE user_id='$user_id'");
$profile = $check->fetch_assoc();

// Fields
$full_name = $_POST['full_name'] ?? '';
$gender = $_POST['gender'] ?? '';
$dob = $_POST['dob'] ?? '';
$status = $_POST['status'] ?? '';
$height = $_POST['height'] ?? '';
$weight = $_POST['weight'] ?? '';
$religion = $_POST['religion'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$education = $_POST['education'] ?? '';
$occupation = $_POST['occupation'] ?? '';
$work_place = $_POST['work_place'] ?? '';
$income = $_POST['income'] ?? '';
$father_name = $_POST['father_name'] ?? '';
$father_occupation = $_POST['father_occupation'] ?? '';
$mother_name = $_POST['mother_name'] ?? '';
$siblings = $_POST['siblings'] ?? '';
$family_type = $_POST['family_type'] ?? '';
$nature = $_POST['nature'] ?? '';
$food = $_POST['food'] ?? '';
$habits = $_POST['habits'] ?? ''; // Drinking/Smoking etc
$hobbies = $_POST['hobbies'] ?? '';
$partner_age_from = $_POST['partner_age_from'] ?? '';
$partner_age_to = $_POST['partner_age_to'] ?? '';
$partner_education = $_POST['partner_education'] ?? '';
$partner_expectations = $_POST['partner_expectations'] ?? '';
$city = $_POST['city'] ?? '';
$residence = $_POST['residence'] ?? '';
$caste = $_POST['caste'] ?? '';
$about = $_POST['about'] ?? '';

$photo_name = $profile['photo'] ?? '';

// Handle Photo Upload
if (isset($_FILES['photo']) && $_FILES['photo']['size'] > 0) {
    $target_dir = "../uploads/photo/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

    $file_ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
    $new_filename = time() . '_' . uniqid() . '.' . $file_ext;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
        $photo_name = $new_filename;
        if ($profile && !empty($profile['photo'])) {
            $old_file = $target_dir . $profile['photo'];
            if (file_exists($old_file)) unlink($old_file);
        }
    }
}

if ($profile) {
    // Update
    $sql = "UPDATE tbl_marriage_profiles SET 
            full_name=?, gender=?, dob=?, status=?, height=?, weight=?, religion=?,
            phone=?, email=?, education=?, occupation=?, work_place=?, income=?,
            father_name=?, father_occupation=?, mother_name=?, siblings=?, family_type=?,
            nature=?, food=?, habits=?, hobbies=?,
            partner_age_from=?, partner_age_to=?, partner_education=?, partner_expectations=?,
            city=?, residence=?, caste=?, about=?, photo=?
            WHERE user_id=?";
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param(
        "ssssssssssssssssssssssiisssssssi",
        $full_name, $gender, $dob, $status, $height, $weight, $religion,
        $phone, $email, $education, $occupation, $work_place, $income,
        $father_name, $father_occupation, $mother_name, $siblings, $family_type,
        $nature, $food, $habits, $hobbies,
        $partner_age_from, $partner_age_to, $partner_education, $partner_expectations,
        $city, $residence, $caste, $about, $photo_name, $user_id
    );
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Profile updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update profile: " . $stmt->error]);
    }
} else {
    // Insert
    $created_at = date("Y-m-d H:i:s");
    $sql = "INSERT INTO tbl_marriage_profiles 
            (user_id, full_name, gender, dob, status, height, weight, religion,
            phone, email, education, occupation, work_place, income,
            father_name, father_occupation, mother_name, siblings, family_type,
            nature, food, habits, hobbies,
            partner_age_from, partner_age_to, partner_education, partner_expectations,
            city, residence, caste, about, photo, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"; // 33 parameters? No.
            // Let's count placeholders.
            // user_id + 30 fields + created_at = 32?
            // Columns: 
            // 1. user_id
            // 2. full_name
            // 3. gender
            // 4. dob
            // 5. status
            // 6. height
            // 7. weight
            // 8. religion
            // 9. phone
            // 10. email
            // 11. education
            // 12. occupation
            // 13. work_place
            // 14. income
            // 15. father_name
            // 16. father_occupation
            // 17. mother_name
            // 18. siblings
            // 19. family_type
            // 20. nature
            // 21. food
            // 22. habits
            // 23. hobbies
            // 24. partner_age_from
            // 25. partner_age_to
            // 26. partner_education
            // 27. partner_expectations
            // 28. city
            // 29. residence
            // 30. caste
            // 31. about
            // 32. photo
            // 33. created_at
            // Total 33 placeholders needed.

    $stmt = $con->prepare($sql);
    $stmt->bind_param(
        "issssssssssssssssssssssiissssssss",
        $user_id,
        $full_name, $gender, $dob, $status, $height, $weight, $religion,
        $phone, $email, $education, $occupation, $work_place, $income,
        $father_name, $father_occupation, $mother_name, $siblings, $family_type,
        $nature, $food, $habits, $hobbies,
        $partner_age_from, $partner_age_to, $partner_education, $partner_expectations,
        $city, $residence, $caste, $about, $photo_name, $created_at
    );

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Profile created successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create profile: " . $stmt->error]);
    }
}
?>
