<?php
include("connection.php");
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$story_id = $_GET['story_id'] ?? 0;

if(!$story_id){
    echo json_encode(['status' => 'error', 'message' => 'Invalid Story ID']);
    exit;
}

$viewers = [];
// Join story_views with members to get user details
$query = "
    SELECT v.viewed_at as date, m.id as user_id, m.name, m.profile_photo 
    FROM tbl_story_views v
    JOIN tbl_members m ON v.viewer_id = m.id
    WHERE v.story_id = ?
    ORDER BY v.viewed_at DESC
";

$stmt = $con->prepare($query);
$stmt->bind_param("i", $story_id);

if($stmt->execute()){
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $viewers[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $viewers]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
