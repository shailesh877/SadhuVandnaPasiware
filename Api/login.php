<?php
include 'headers.php';
include 'connection.php';

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Support both JSON and Form Data
$email = $data['email'] ?? $_POST['email'] ?? '';
$password = $data['password'] ?? $_POST['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(["status" => "error", "message" => "Email and Password required"]);
    exit;
}

$stmt = $con->prepare("SELECT * FROM tbl_members WHERE email=? OR mobile=? LIMIT 1");
$stmt->bind_param("ss", $email, $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 1) {
    $user = $res->fetch_assoc();

    if ($user['status'] == 'Blocked') {
        echo json_encode(["status" => "error", "message" => "Account is blocked"]);
        exit;
    }

    if (password_verify($password, $user['password'])) {
        // Success
        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "data" => [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "mobile" => $user['mobile'],
                "photo" => $user['profile_photo'],
                "city" => $user['city'],
                "role" => "user", // Generic role
                "token" =>  base64_encode($user['email'] . '::' . time()) // Simple mock token for now
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid password"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "User not found"]);
}
?>
