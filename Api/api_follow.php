<?php
include("connection.php");
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
        } else {
            // New request
            $stmt = $con->prepare("INSERT INTO tbl_followers (follower_id, following_id, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("ii", $follower_id, $following_id);
            $stmt->execute();
            $status = "requested";
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

echo json_encode(["ok" => false, "message" => "Invalid action"]);
?>
