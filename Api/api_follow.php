<?php
include("connection.php");
include("push_helper.php");
header('Content-Type: application/json');

// Get action
$action = $_REQUEST['action'] ?? '';

// Support both POST (form-data/json) and GET
$input = json_decode(file_get_contents("php://input"), true);
$current_user_id = intval($_POST['current_user_id'] ?? $input['current_user_id'] ?? $_GET['current_user_id'] ?? 0);

if(!$current_user_id){
    echo json_encode(["ok" => false, "message" => "Not logged in (Missing current_user_id)"]);
    exit;
}

$follower_id = $current_user_id;

if($action === 'follow'){
    $following_id = intval($_POST['user_id'] ?? $input['user_id'] ?? $_GET['user_id'] ?? 0);
    
    if(!$following_id) {
        echo json_encode(["ok" => false, "message" => "Target user missing"]);
        exit;
    }
    
    if($follower_id == $following_id){
        echo json_encode(["ok" => false, "message" => "You cannot follow yourself"]);
        exit;
    }

    $check = $con->query("SELECT status FROM tbl_followers WHERE follower_id=$follower_id AND following_id=$following_id");
    
    if($check->num_rows == 0){
        // Fetch sender name for notification
        $sender_name = "Someone";
        $sQ = $con->query("SELECT name FROM tbl_members WHERE id = $follower_id LIMIT 1");
        if($sRow = $sQ->fetch_assoc()) $sender_name = $sRow['name'];

        // Check if the other person has a pending request to ME
        $check_other = $con->query("SELECT id FROM tbl_followers WHERE follower_id=$following_id AND following_id=$follower_id");
        
        if($check_other->num_rows > 0){
            // It's a "Follow Back" or "Accept" -> Both become accepted (Connected)
            $stmt = $con->prepare("INSERT INTO tbl_followers (follower_id, following_id, status) VALUES (?, ?, 'accepted')");
            $stmt->bind_param("ii", $follower_id, $following_id);
            $stmt->execute();
            
            // Also update the other person's status to accepted
            $con->query("UPDATE tbl_followers SET status='accepted' WHERE follower_id=$following_id AND following_id=$follower_id");
            $status = "connected";

            // Push Notification: Accepted
            sendExpoPushNotification($con, $following_id, "New Connection", "$sender_name accepted your request and followed you back!", ["type" => "follow", "user_id" => $follower_id]);
        } else {
            // New request
            $stmt = $con->prepare("INSERT INTO tbl_followers (follower_id, following_id, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("ii", $follower_id, $following_id);
            $stmt->execute();
            $status = "requested";

            // Push Notification: Request
            sendExpoPushNotification($con, $following_id, "Follow Request", "$sender_name wants to connect with you.", ["type" => "follow_request", "user_id" => $follower_id]);
        }
    } else {
        // Already following or requested -> Remove
        $con->query("DELETE FROM tbl_followers WHERE follower_id=$follower_id AND following_id=$following_id");
        $status = "unfollowed";
    }

    // Get updated counts (Only accepted followers count normally)
    $followers_count = $con->query("SELECT COUNT(*) FROM tbl_followers WHERE following_id=$following_id AND status='accepted'")->fetch_row()[0];
    
    echo json_encode(["ok" => true, "status" => $status, "followers_count" => $followers_count]);
    exit;
}

if(in_array($action, ['fetch_followers', 'fetch_following', 'fetch_friends', 'fetch_requested', 'fetch_sent', 'fetch_all_members'])){
    $uid = intval($_GET['user_id'] ?? $_POST['user_id'] ?? $input['user_id'] ?? 0);
    
    if($action === 'fetch_followers'){
        $sql = "SELECT m.id, m.name, m.profile_photo, m.city 
                FROM tbl_members m 
                JOIN tbl_followers f ON m.id = f.follower_id 
                WHERE f.following_id = $uid";
    } elseif($action === 'fetch_following') {
        $sql = "SELECT m.id, m.name, m.profile_photo, m.city 
                FROM tbl_members m 
                JOIN tbl_followers f ON m.id = f.following_id 
                WHERE f.follower_id = $uid";
    } elseif($action === 'fetch_friends') {
        $sql = "SELECT m.id, m.name, m.profile_photo, m.city 
                FROM tbl_members m 
                JOIN tbl_followers f ON m.id = f.follower_id 
                WHERE f.following_id = $uid AND f.status='accepted'";
    } elseif($action === 'fetch_requested') {
        $sql = "SELECT m.id, m.name, m.profile_photo, m.city 
                FROM tbl_members m 
                JOIN tbl_followers f ON m.id = f.follower_id 
                WHERE f.following_id = $uid AND f.status='pending'";
    } elseif($action === 'fetch_sent') {
        $sql = "SELECT m.id, m.name, m.profile_photo, m.city 
                FROM tbl_members m 
                JOIN tbl_followers f ON m.id = f.following_id 
                WHERE f.follower_id = $uid AND f.status='pending'";
    } elseif($action === 'fetch_all_members') {
        $sql = "SELECT id, name, profile_photo, city FROM tbl_members WHERE id != $follower_id ORDER BY name ASC";
    }
    
    $res = $con->query($sql);
    $list = [];
    while($row = $res->fetch_assoc()){
        // Check if I follow them
        $check_i_follow = $con->query("SELECT status FROM tbl_followers WHERE follower_id=$follower_id AND following_id=".$row['id']);
        if ($r = $check_i_follow->fetch_assoc()) {
            $row['i_follow'] = true;
            $row['my_status'] = $r['status'];
        } else {
            $row['i_follow'] = false;
            $row['my_status'] = '';
        }
        
        // Check if they follow me
        $check_they_follow = $con->query("SELECT id FROM tbl_followers WHERE follower_id=".$row['id']." AND following_id=$follower_id");
        $row['follows_me'] = $check_they_follow->num_rows > 0;

        $list[] = $row;
    }
    echo json_encode(["ok" => true, "list" => $list]);
    exit;
}

if($action === 'get_counts'){
    $uid = intval($_GET['user_id'] ?? $_POST['user_id'] ?? $input['user_id'] ?? 0);
    
    if(!$uid) {
        echo json_encode(["ok" => false, "message" => "Target user missing"]);
        exit;
    }
    
    // As per website logic, friends = accepted followers
    $friends = $con->query("SELECT COUNT(*) FROM tbl_followers WHERE following_id=$uid AND status='accepted'")->fetch_row()[0];
    $requested = $con->query("SELECT COUNT(*) FROM tbl_followers WHERE following_id=$uid AND status='pending'")->fetch_row()[0];
    $sent = $con->query("SELECT COUNT(*) FROM tbl_followers WHERE follower_id=$uid AND status='pending'")->fetch_row()[0];
    $posts = $con->query("SELECT COUNT(*) FROM tbl_posts WHERE user_id=$uid")->fetch_row()[0];
    
    $is_following = false;
    $is_requested = false;
    $is_connected = false;
    $follows_me = false;
    
    if($follower_id){
        // My follow to them
        $check = $con->query("SELECT status FROM tbl_followers WHERE follower_id=$follower_id AND following_id=$uid");
        if($row = $check->fetch_assoc()){
            if($row['status'] === 'accepted') $is_following = true;
            else $is_requested = true;
        }
        
        // Their follow to me
        $check_me = $con->query("SELECT status FROM tbl_followers WHERE follower_id=$uid AND following_id=$follower_id");
        if($row_me = $check_me->fetch_assoc()){
            $follows_me = true;
            if($row_me['status'] === 'accepted' && $is_following) $is_connected = true;
        }
    }

    echo json_encode([
        "ok" => true,
        "friends" => $friends,
        // The website returned all followers count for display, even pending sometimes. 
        // Replicating exactly what follow_action.php did.
        "followers" => $con->query("SELECT COUNT(*) FROM tbl_followers WHERE following_id=$uid")->fetch_row()[0],
        "following" => $con->query("SELECT COUNT(*) FROM tbl_followers WHERE follower_id=$uid")->fetch_row()[0],
        "requested" => $requested,
        "sent" => $sent,
        "posts" => $posts,
        "is_following" => $is_following,
        "is_requested" => $is_requested,
        "is_connected" => $is_connected,
        "follows_me" => $follows_me
    ]);
    exit;
}

if ($action === 'fetch_suggestions') {
    $limit = intval($_GET['limit'] ?? 10);
    
    // Fetch users NOT current user AND NOT already followed/requested
    $sql = "SELECT id, name as full_name, profile_photo as photo, city 
            FROM tbl_members 
            WHERE id != $current_user_id 
            AND id NOT IN (
                SELECT following_id FROM tbl_followers WHERE follower_id = $current_user_id
            )
            ORDER BY RAND() 
            LIMIT $limit";
            
    $res = $con->query($sql);
    $list = [];
    while($row = $res->fetch_assoc()){
        $row['proposal_status'] = null; // Compatibility with component
        $list[] = $row;
    }
    echo json_encode(["status" => "success", "ok" => true, "data" => $list]);
    exit;
}

echo json_encode(["ok" => false, "message" => "Invalid action"]);
?>
