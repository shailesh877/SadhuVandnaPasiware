<?php
include 'headers.php';
include 'connection.php';

$user_id = $_POST['user_id'] ?? 0;
$status = $_POST['status'] ?? '';
$link = $_POST['link'] ?? '';

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

$uploadDir = "../uploads/posts/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

$mediaFiles = [];

if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
    foreach ($_FILES['media']['name'] as $key => $filename) {
        $tmpName = $_FILES['media']['tmp_name'][$key];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $newName = uniqid('post_') . '.' . $ext;
        $targetFile = $uploadDir . $newName;

        if (move_uploaded_file($tmpName, $targetFile)) {
            $mediaFiles[] = $newName;
        }
    }
}

$mediaString = !empty($mediaFiles) ? implode(",", $mediaFiles) : NULL;
date_default_timezone_set("Asia/Kolkata");
$date = date("Y-m-d H:i:s");

$stmt = $con->prepare("INSERT INTO tbl_posts (user_id, status, media, link, created_at) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $user_id, $status, $mediaString, $link, $date);

if ($stmt->execute()) {
    $post_id = $stmt->insert_id;
    echo json_encode(["status" => "success", "message" => "Post created", "data" => ["id" => $post_id]]);
    
    // 🔥 Send Push Notification to Followers
    try {
        include_once 'push_helper.php';
        $author_name = "Someone";
        $uQ = $con->query("SELECT name FROM tbl_members WHERE id = '$user_id' LIMIT 1");
        if($uR = $uQ->fetch_assoc()) $author_name = $uR['name'];

        // Get all followers (accepted or pending, matching app's open social style)
        $fQ = $con->query("SELECT follower_id FROM tbl_followers WHERE following_id = '$user_id'");
        while($fR = $fQ->fetch_assoc()) {
            $follower_id = $fR['follower_id'];
            sendExpoPushNotification(
                $con, 
                $follower_id, 
                "New Post", 
                "$author_name shared a new post.", 
                ["type" => "post", "postId" => strval($post_id)]
            );
        }
    } catch (Exception $e) {
        // Silently fail push to not break post creation
    }
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}
?>
