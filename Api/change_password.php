<?php
include("connection.php");
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$user_id = $_POST['user_id'] ?? 0;
$current = trim($_POST['current_password'] ?? '');
$new     = trim($_POST['new_password'] ?? '');

if(!$user_id || empty($current) || empty($new)){
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Fetch current password
$stmt = $con->prepare("SELECT password FROM tbl_members WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows == 0){
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

$row = $res->fetch_assoc();

if(password_verify($current, $row['password'])){
    // Update password
    $hashed = password_hash($new, PASSWORD_DEFAULT);
    $update = $con->prepare("UPDATE tbl_members SET password=? WHERE id=?");
    $update->bind_param("si", $hashed, $user_id);
    
    if($update->execute()){
        echo json_encode(['status' => 'success', 'message' => 'Password changed successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Incorrect current password']);
}
?>
