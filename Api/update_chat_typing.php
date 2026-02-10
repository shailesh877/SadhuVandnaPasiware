// Api/update_chat_typing.php
// Updates the typing status of a user relative to a target user.
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

include("connection.php");
$con->set_charset("utf8mb4");

date_default_timezone_set('Asia/Kolkata');

$profile_id = intval($_POST['profile_id'] ?? 0);
$target_profile_id = intval($_POST['receiver_id'] ?? 0);
$is_typing = intval($_POST['is_typing'] ?? 0);

if(!$profile_id || !$target_profile_id){
    echo json_encode(['status'=>'error', 'message'=>'Missing IDs']);
    exit;
}

// Insert or update typing status
$stmt = $con->prepare("INSERT INTO tbl_typing (profile_id, target_profile_id, is_typing, updated_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE is_typing=VALUES(is_typing), updated_at=NOW()");
$stmt->bind_param("iii", $profile_id, $target_profile_id, $is_typing);

if($stmt->execute()){
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error', 'message'=>'DB Error']);
}
?>
