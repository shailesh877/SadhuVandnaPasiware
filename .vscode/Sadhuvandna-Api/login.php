<?php
include 'headers.php';
include 'connection.php';

$user = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if(empty($user) || empty($password)){
    echo json_encode(["status" => "error", "message" => "Email and Password required"]);
    exit;
}

// Check by email or mobile
$stmt = $con->prepare("SELECT * FROM tbl_members WHERE email=? OR mobile=? LIMIT 1");
$stmt->bind_param("ss", $user, $user);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 1){
    $row = $result->fetch_assoc();

    if ($row['status'] == 'Blocked') {
        echo json_encode(["status" => "error", "message" => "Account Blocked"]);
        exit;
    }

    if(password_verify($password, $row['password'])){
        echo json_encode([
            "status" => "success",
            "message" => "Login Successful",
            "user" => [
                "id" => $row['id'],
                "name" => $row['name'],
                "email" => $row['email'],
                "profile_photo" => $row['profile_photo']
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid Password"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "User not found"]);
}
?>
