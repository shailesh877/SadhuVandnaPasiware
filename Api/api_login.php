<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("connection.php");
header('Content-Type: application/json');
session_start();

if (!$con) {
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

$user = trim($_POST['user'] ?? '');
$password = $_POST['password'] ?? '';

if(empty($user) || empty($password)){
    echo json_encode(["status" => "error", "message" => "Please fill all fields"]);
    exit;
}

$stmt = $con->prepare("SELECT * FROM tbl_members WHERE email=? OR mobile=? LIMIT 1");
if (!$stmt) {
     echo json_encode(["status" => "error", "message" => "Query preparation failed: " . $con->error]);
     exit;
}

$stmt->bind_param("ss", $user, $user);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 1){
    $row = $result->fetch_assoc();

    if ($row['status'] == 'Blocked') {
        echo json_encode(["status" => "error", "message" => "Your account is blocked"]);
        exit;
    }

    if(password_verify($password, $row['password'])){
        $userData = [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'mobile' => $row['mobile'],
            'profile_photo' => $row['profile_photo']
        ];
        echo json_encode(["status" => "success", "message" => "Login Successful", "user" => $userData]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid password"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "User not found"]);
}
?>
