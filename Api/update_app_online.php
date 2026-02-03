// Api/update_app_online.php
// Updates the current user's last_active timestamp.
// Should be called periodically by the app to show as "Online".
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

include("connection.php");
$con->set_charset("utf8mb4");

date_default_timezone_set('Asia/Kolkata');

$user_id = intval($_POST['user_id'] ?? 0);

if(!$user_id){
    echo json_encode(['status'=>'error']);
    exit;
}

// Update last_active in tbl_members
$con->query("UPDATE tbl_members SET last_active=NOW() WHERE id='$user_id'");

echo json_encode(['status'=>'success']);
?>
