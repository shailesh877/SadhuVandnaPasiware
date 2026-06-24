<?php
include 'headers.php';
include 'connection.php';

$user_id = $_GET['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID is required."]);
    exit;
}

$query = $con->query("SELECT status FROM tbl_anchor_applications WHERE user_id = '$user_id' LIMIT 1");
if ($query && $query->num_rows > 0) {
    $row = $query->fetch_assoc();
    echo json_encode([
        "status" => "success",
        "applied" => true,
        "anchor_status" => $row['status'] // Pending, Approved, Rejected
    ]);
} else {
    echo json_encode([
        "status" => "success",
        "applied" => false,
        "anchor_status" => null
    ]);
}
?>
