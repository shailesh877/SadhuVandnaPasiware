<?php
include("connection.php");
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$user_id  = $_POST['user_id'] ?? 0;
$token    = $_POST['token'] ?? '';
$platform = $_POST['platform'] ?? 'android';

if(!$user_id || empty($token)){
    echo json_encode(['status' => 'error', 'message' => 'User ID and Token required']);
    exit;
}

// Check if table exists, if not create it (Simple migration for now)
$checkTable = $con->query("SHOW TABLES LIKE 'tbl_device_tokens'");
if($checkTable->num_rows == 0) {
    $sql = "CREATE TABLE tbl_device_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        platform VARCHAR(50) DEFAULT 'android',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_token (user_id, token)
    )";
    $con->query($sql);
}

// Insert or ignore (to avoid duplicates for same user-token pair)
// Using INSERT IGNORE or ON DUPLICATE KEY UPDATE could work.
// Let's just try to insert, if it fails due to unique constraint, it's fine.
// Better: Check if token exists for this user.

$check = $con->prepare("SELECT id FROM tbl_device_tokens WHERE user_id=? AND token=?");
$check->bind_param("is", $user_id, $token);
$check->execute();
$res = $check->get_result();

if($res->num_rows == 0){
    $stmt = $con->prepare("INSERT INTO tbl_device_tokens (user_id, token, platform) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $token, $platform);
    if($stmt->execute()){
        echo json_encode(['status' => 'success', 'message' => 'Token registered']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'success', 'message' => 'Token already exists']);
}
?>
