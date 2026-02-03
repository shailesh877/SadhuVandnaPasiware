// Api/get_chat_user_status.php
// Fetches online status, last seen, and typing status of a target user.
header('Content-Type: application/json');
error_reporting(0); // Disable error reporting to prevent JSON corruption
ini_set('display_errors', 0);

include("connection.php");
$con->set_charset("utf8mb4");

date_default_timezone_set('Asia/Kolkata');

$profile_id = intval($_GET['profile_id'] ?? 0); // The profile we are checking (Receiver)
$my_profile_id = intval($_GET['my_profile_id'] ?? 0); // Me (to check if they are typing to ME)

if(!$profile_id){ 
    echo json_encode(['status'=>'error', 'message'=>'Missing ID']); 
    exit; 
}

$response = [
    'status' => 'success',
    'online' => false,
    'last_active' => null,
    'is_typing' => false
];

// 1. Check Online/Last Active Status
// Join tbl_marriage_profiles to get user_id, then check tbl_members for last_active
// Logic: If last_active is within 25 seconds (web uses 25s), user is online.
$res = $con->query("SELECT (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(m.last_active) < 30) AS online, m.last_active 
                    FROM tbl_members m 
                    JOIN tbl_marriage_profiles mp ON mp.user_id=m.id 
                    WHERE mp.id='$profile_id' LIMIT 1");

if($res && $row = $res->fetch_assoc()){
    $response['online'] = (bool)$row['online'];
    if(!$response['online'] && !empty($row['last_active'])){
        // Format: "15 Dec, 10:30 AM" or "Today, 10:30 AM" logic can be handled here or frontend.
        // Frontend handling is better for timezone adaptability but server formatted string is easier for now as per website logic.
        $response['last_active'] = date("d M, h:i A", strtotime($row['last_active']));
    }
}

// 2. Check Typing Status (Is 'profile_id' typing TO 'my_profile_id'?)
// The typing entry must be recent (e.g., updated within last 5-10 seconds) to be valid, otherwise clean up might have missed.
// Web logic just checks `is_typing`. We can replicate that.
if($my_profile_id){
    $typ = $con->query("SELECT is_typing, updated_at FROM tbl_typing WHERE profile_id='$profile_id' AND target_profile_id='$my_profile_id' LIMIT 1");
    if($typ && $typ->num_rows > 0){
        $r = $typ->fetch_assoc();
        // Check if typing status was updated recently (e.g. within 10s) to avoid stuck "typing..."
        $updated_ts = strtotime($r['updated_at']);
        $now = time();
        if($r['is_typing'] == 1 && ($now - $updated_ts < 10)){
            $response['is_typing'] = true;
        }
    }
}

echo json_encode($response);
?>
