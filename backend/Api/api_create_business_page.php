<?php
include 'headers.php';
include 'connection.php';

// Get JSON input or form data
$data = json_decode(file_get_contents("php://input"), true);

// Setup Database Automatically if it doesn't exist
try {
    // 1. Update tbl_members (Silence errors if column exists in older MySQL)
    @$con->query("ALTER TABLE tbl_members 
        ADD COLUMN is_business TINYINT(1) DEFAULT 0,
        ADD COLUMN parent_userid INT DEFAULT NULL,
        ADD COLUMN category VARCHAR(100) DEFAULT NULL");
} catch(Exception $e) {}

try {
    // 2. Create tbl_business_details table (no FK - tbl_members uses MyISAM engine)
    $con->query("CREATE TABLE IF NOT EXISTS tbl_business_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        phone_no VARCHAR(20) DEFAULT NULL,
        email VARCHAR(100) DEFAULT NULL,
        website_url VARCHAR(255) DEFAULT NULL,
        social_media_account VARCHAR(255) DEFAULT NULL,
        country VARCHAR(100) DEFAULT NULL,
        state VARCHAR(100) DEFAULT NULL,
        district VARCHAR(100) DEFAULT NULL,
        full_address TEXT DEFAULT NULL,
        pincode VARCHAR(20) DEFAULT NULL,
        opening_hours VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch(Exception $e) {}

try {
    // 3. Ensure full_address exists in case table was created earlier without it
    @$con->query("ALTER TABLE tbl_business_details ADD COLUMN full_address TEXT DEFAULT NULL AFTER district");
} catch(Exception $e) {}


$parent_userid = $data['user_id'] ?? $_POST['user_id'] ?? '';
$businessname = $data['businessname'] ?? $_POST['businessname'] ?? '';
$category = $data['category'] ?? $_POST['category'] ?? '';

// Business Details
$phone_no = $data['phone_no'] ?? $_POST['phone_no'] ?? '';
$email = $data['email'] ?? $_POST['email'] ?? '';
$website_url = $data['website_url'] ?? $_POST['website_url'] ?? '';
$social_media_account = $data['social_media_account'] ?? $_POST['social_media_account'] ?? '';
$country = $data['country'] ?? $_POST['country'] ?? '';
$state = $data['state'] ?? $_POST['state'] ?? '';
$district = $data['district'] ?? $_POST['district'] ?? '';
$full_address = $data['full_address'] ?? $_POST['full_address'] ?? '';
$pincode = $data['pincode'] ?? $_POST['pincode'] ?? '';
$opening_hours = $data['opening_hours'] ?? $_POST['opening_hours'] ?? '';

if (!$parent_userid || !$businessname || !$category) {
    echo json_encode(["status" => "error", "message" => "Required fields are missing"]);
    exit;
}

// Ensure the parent user exists
$stmt = $con->prepare("SELECT id FROM tbl_members WHERE id=?");
$stmt->bind_param("i", $parent_userid);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid user_id"]);
    exit;
}

// 1. Insert into tbl_members as a business page
$is_business = 1;
// Generate mock data for required fields in tbl_members that don't apply to a business page
$mock_email = "business_" . time() . "@page.com";
$mock_mobile = time(); // Random number to avoid any unique checks
$mock_empty = "";
$date_now = date('Y-m-d');

// The following fields in tbl_members are NOT NULL and have no default value:
// name, email, mobile, password, dob, gender, city, cast, profile_photo, date, status (default Pending)
$insert_member = $con->prepare("INSERT INTO tbl_members (name, email, mobile, password, dob, gender, city, cast, profile_photo, date, is_business, parent_userid, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$insert_member->bind_param("ssssssssssiis", 
    $businessname, 
    $email,        // Actual business email
    $phone_no,     // Actual business phone no
    $mock_empty, 
    $mock_empty, 
    $mock_empty, 
    $district,     // Using district for city
    $mock_empty, 
    $mock_empty, 
    $date_now, 
    $is_business, 
    $parent_userid, 
    $category
);

if ($insert_member->execute()) {
    $new_business_id = $insert_member->insert_id;

    // 2. Insert into tbl_business_details
    $insert_details = $con->prepare("INSERT INTO tbl_business_details (user_id, phone_no, email, website_url, social_media_account, country, state, district, full_address, pincode, opening_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert_details->bind_param("issssssssss", $new_business_id, $phone_no, $email, $website_url, $social_media_account, $country, $state, $district, $full_address, $pincode, $opening_hours);
    
    if ($insert_details->execute()) {
        echo json_encode(["status" => "success", "message" => "Business Page created successfully", "page_id" => $new_business_id]);
    } else {
        // Rollback member creation if details fail (optional but good practice)
        $con->query("DELETE FROM tbl_members WHERE id=$new_business_id");
        echo json_encode(["status" => "error", "message" => "Failed to save business details: " . $con->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Failed to create business page: " . $con->error]);
}
?>
