<?php
include("connection.php");

$response = array();

// 1. Get Params
$post_id = $_POST['post_id'] ?? 0;
$user_id = $_POST['user_id'] ?? 0;

if(!$post_id || !$user_id){
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit;
}

// 2. Fetch Post to Check Ownership
$stmt = $con->prepare("SELECT user_id, media FROM tbl_posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
if($stmt->execute()){
    $res = $stmt->get_result();
    if($res->num_rows > 0){
        $post = $res->fetch_assoc();
        
        // 3. Ownership Check
        if($post['user_id'] != $user_id){
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        // 4. Delete Media Files
        if(!empty($post['media'])){
            $medias = explode(',', $post['media']);
            foreach($medias as $m){
                if(trim($m) != ''){
                    $path = "../uploads/posts/" . trim($m);
                    if(file_exists($path)) unlink($path);
                }
            }
        }

        // 5. Delete Related Data (Comments, Likes)
        $con->query("DELETE FROM tbl_comments WHERE post_id = $post_id");
        $con->query("DELETE FROM tbl_likes WHERE post_id = $post_id");
        $con->query("DELETE FROM tbl_posts WHERE id = $post_id");

        echo json_encode(['status' => 'success', 'message' => 'Post deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Post not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
