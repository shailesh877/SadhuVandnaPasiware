<?php
include 'headers.php';
include 'connection.php';

$jsonInput = json_decode(file_get_contents('php://input'), true);
$title = trim($_POST['title'] ?? $_REQUEST['title'] ?? $jsonInput['title'] ?? '');
$user_id = intval($_POST['user_id'] ?? $_REQUEST['user_id'] ?? $jsonInput['user_id'] ?? 0);

if (empty($title)) {
    echo json_encode(["status" => "error", "message" => "Title is required."]);
    exit;
}

if ($user_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid user."]);
    exit;
}

date_default_timezone_set("Asia/Kolkata");
$date = date("Y-m-d H:i:s");
$posted_by = 'Anchor';

$u_stmt = $con->query("SELECT name FROM tbl_users WHERE id = $user_id");
if ($u_stmt && $u_stmt->num_rows > 0) {
    $u_row = $u_stmt->fetch_assoc();
    $posted_by = $u_row['name'];
}

$is_active = 0; // Requires admin approval

$stmt = $con->prepare("INSERT INTO tbl_breaking_news (title, posted_by, poster_id, is_active, created_at) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssiis", $title, $posted_by, $user_id, $is_active, $date);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Breaking news submitted for approval."]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
}
?>
