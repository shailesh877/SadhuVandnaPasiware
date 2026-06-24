<?php
include 'headers.php';
include 'connection.php';

$user_id = $_GET['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID is required."]);
    exit;
}

// 1. Fetch anchor application fee from global settings
$fee = 100; // default fee
$settings_q = $con->query("SELECT `value` FROM tbl_settings WHERE `key` = 'anchor_profile_fee' LIMIT 1");
if ($settings_q && $settings_q->num_rows > 0) {
    $settings_row = $settings_q->fetch_assoc();
    $fee = intval($settings_row['value']);
}

// 2. Check if user has paid the anchor fee
$checkPaid = $con->query("
    SELECT id FROM tbl_wallet 
    WHERE user_id = '$user_id' 
    AND payment_type = 'anchor' 
    AND status = 'success' 
    LIMIT 1
");
$paid = ($checkPaid && $checkPaid->num_rows > 0);

// 3. Check application status
$query = $con->query("SELECT status FROM tbl_anchor_applications WHERE user_id = '$user_id' LIMIT 1");
if ($query && $query->num_rows > 0) {
    $row = $query->fetch_assoc();
    echo json_encode([
        "status" => "success",
        "applied" => true,
        "anchor_status" => $row['status'], // Pending, Approved, Rejected
        "paid" => $paid,
        "fee" => $fee,
        "payment_url" => "payment_anchor.php?user_id=" . $user_id
    ]);
} else {
    echo json_encode([
        "status" => "success",
        "applied" => false,
        "anchor_status" => null,
        "paid" => $paid,
        "fee" => $fee,
        "payment_url" => "payment_anchor.php?user_id=" . $user_id
    ]);
}
?>
