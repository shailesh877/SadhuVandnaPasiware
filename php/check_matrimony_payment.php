<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    include 'connection.php';

    // Handle JSON Input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $user_id = intval($_REQUEST['user_id'] ?? $data['user_id'] ?? 0);

    if (!$user_id) {
        echo json_encode(["status" => "error", "message" => "User ID required"]);
        exit;
    }

    // 1. Fetch the Matrimony Profile Fee for this user
    $fee = 500; // default fee
    $userQ = $con->query("SELECT matrimony_profile_fee FROM tbl_members WHERE id = '$user_id' LIMIT 1");
    if ($userQ && $userQ->num_rows > 0) {
        $userRow = $userQ->fetch_assoc();
        if (isset($userRow['matrimony_profile_fee']) && intval($userRow['matrimony_profile_fee']) > 0) {
            $fee = intval($userRow['matrimony_profile_fee']);
        }
    }

    // 2. Check if the user has already paid the matrimony fee
    // Check in tbl_wallet for a successful 'matrimony' payment
    $checkPaid = $con->query("
        SELECT id FROM tbl_wallet 
        WHERE user_id = '$user_id' 
        AND payment_type = 'matrimony' 
        AND status = 'success' 
        LIMIT 1
    ");

    $paid = ($checkPaid && $checkPaid->num_rows > 0);

    echo json_encode([
        "status" => "success",
        "paid" => $paid,
        "fee" => $fee,
        "payment_url" => "payment_matrimony.php?user_id=" . $user_id
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
