<?php
include 'headers.php';
include 'connection.php';

$user_id = $_POST['user_id'] ?? 0;
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$education = $_POST['education'] ?? '';

if (!$user_id || !$name || !$phone || !$email || !$education) {
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit;
}

$uploadDir = "../uploads/anchor_applications/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

$photo = $_FILES['photo'] ?? null;
$aadhaar = $_FILES['aadhaar'] ?? null;
$resume = $_FILES['resume'] ?? null;

if (!$photo || !$aadhaar || !$resume) {
    echo json_encode(["status" => "error", "message" => "All files (Photo, Aadhaar, Resume) are required."]);
    exit;
}

$photoName = uniqid('photo_') . '_' . basename($photo['name']);
$aadhaarName = uniqid('aadhaar_') . '_' . basename($aadhaar['name']);
$resumeName = uniqid('resume_') . '_' . basename($resume['name']);

move_uploaded_file($photo['tmp_name'], $uploadDir . $photoName);
move_uploaded_file($aadhaar['tmp_name'], $uploadDir . $aadhaarName);
move_uploaded_file($resume['tmp_name'], $uploadDir . $resumeName);

$status = 'Pending';
$date = date("Y-m-d H:i:s");

// Insert or update into tbl_anchor_applications
$stmt = $con->prepare("
    INSERT INTO tbl_anchor_applications (user_id, name, phone, email, education, photo, aadhaar, resume, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
    name=?, phone=?, email=?, education=?, photo=?, aadhaar=?, resume=?, status='Pending', created_at=?
");
$stmt->bind_param("isssssssssssssssss", 
    $user_id, $name, $phone, $email, $education, $photoName, $aadhaarName, $resumeName, $status, $date,
    $name, $phone, $email, $education, $photoName, $aadhaarName, $resumeName, $date
);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Application submitted successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
}
?>
