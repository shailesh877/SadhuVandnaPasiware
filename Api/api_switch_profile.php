<?php
include 'headers.php';
include 'connection.php';

$data = json_decode(file_get_contents("php://input"), true);
$current_user_id = $data['user_id'] ?? $_POST['user_id'] ?? '';
$target_profile_id = $data['target_profile_id'] ?? $_POST['target_profile_id'] ?? '';

if (!$current_user_id || !$target_profile_id) {
    echo json_encode(["status" => "error", "message" => "user_id and target_profile_id are required"]);
    exit;
}

// Security Check: Ensure the target profile belongs to the current user (either it's their page, or they are switching back to their main profile)
$stmt = $con->prepare("SELECT * FROM tbl_members WHERE id = ? AND (parent_userid = ? OR id = (SELECT parent_userid FROM tbl_members WHERE id = ?))");
$stmt->bind_param("iii", $target_profile_id, $current_user_id, $current_user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    // Check if they are just trying to switch to themselves (fallback)
    if ($current_user_id == $target_profile_id) {
        $stmt = $con->prepare("SELECT * FROM tbl_members WHERE id = ?");
        $stmt->bind_param("i", $target_profile_id);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        echo json_encode(["status" => "error", "message" => "Unauthorized profile switch"]);
        exit;
    }
}

$user = $res->fetch_assoc();

if ($user['status'] == 'Blocked') {
    echo json_encode(["status" => "error", "message" => "This account is blocked"]);
    exit;
}

// Return the new profile data (Mocking token for session handling on frontend)
echo json_encode([
    "status" => "success",
    "message" => "Profile switched successfully",
    "data" => [
        "id" => $user['id'],
        "name" => $user['name'],
        "email" => $user['email'],
        "mobile" => $user['mobile'],
        "photo" => $user['profile_photo'],
        "city" => $user['city'],
        "is_business" => $user['is_business'],
        "category" => $user['category'],
        "parent_userid" => $user['parent_userid'],
        "role" => ($user['is_business'] == 1) ? "business" : "user",
        "token" => base64_encode($user['email'] . '::' . time()) // Simulate new token
    ]
]);
?>
