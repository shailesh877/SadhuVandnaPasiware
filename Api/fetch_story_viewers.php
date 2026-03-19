<?php
include("headers.php");
include("connection.php");

// Get viewers for a story
$story_id = intval($_GET['story_id'] ?? 0);

if ($story_id > 0) {
    // USING viewer_id AND viewed_at AS PER YOUR SERVER'S DATABASE STRUCTURE
    $res = $con->query("
        SELECT v.viewed_at as date, m.name, m.profile_photo 
        FROM tbl_story_views v
        JOIN tbl_members m ON v.viewer_id = m.id
        WHERE v.story_id = $story_id
        ORDER BY v.viewed_at DESC
    ");
    
    $viewers = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $viewers[] = [
                "name" => htmlspecialchars($row['name']),
                "profile_photo" => htmlspecialchars($row['profile_photo']),
                "date" => date("d M Y, h:i A", strtotime($row['date']))
            ];
        }
    }
    
    echo json_encode(["status" => "success", "data" => $viewers]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid story ID"]);
}
?>
