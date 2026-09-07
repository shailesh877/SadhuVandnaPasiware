<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'headers.php';
include 'connection.php';

if (!$con) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

date_default_timezone_set('Asia/Kolkata');

// Get active stories (uploaded in last 24 hours)

$current_time = time();
$twenty_four_hours_ago = $current_time - (24 * 60 * 60);

// Use a JOIN to get users who have stories in the last 24 hours
// Assuming 'date' column in tbl_stories is Y-m-d H:i:s as set in create_story.php
// If it is stored as string, we can try SQL comparison or PHP filtering. 
// Since we control create_story, we rely on that format.

// 1. Fetch all valid stories from last 24 hours
// Use proper paths
$httpProtocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_url = $httpProtocol . "://" . $host . "/Sadhuvandna-Api"; // Adjust if Api folder logic differs
// Actually, let's just use what other APIs use or relative if fine, but App needs absolute.
// Current API is in /Api/, so uploads are in ../uploads/
// Let's explicitly build URL based on where this script runs.
// Better: Define base URL directly or use relative if App handles it. 
// App's API_BASE_URL usually points to .../Api
// So images are at .../uploads/
// Let's send RELATIVE paths that the App can easily prepend, OR send FULL paths.
// Previous code sent just filename. App tried to prepend.
// Let's send FULL URL to be safe, or consistently use filename and let App prepend.
// But user said "images not showing".
// Let's construct full URL to be 100% sure.

$base_root = $httpProtocol . "://" . $host . dirname(dirname($_SERVER['PHP_SELF'])); // Moves out of /Api/
$photo_url = $base_root . "/uploads/photo/";
$story_url = $base_root . "/uploads/stories/";

$users_map = [];

$query = "SELECT s.id as story_id, s.image, s.date, s.user_id, m.name, m.profile_photo 
          FROM tbl_stories s 
          JOIN tbl_members m ON s.user_id = m.id 
          WHERE s.date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
          ORDER BY s.date ASC";

$result = $con->query($query);

while($row = $result->fetch_assoc()){
    $uid = $row['user_id'];
    
    // Fix Profile Photo
    $p_photo = $row['profile_photo'];
    if($p_photo && !str_starts_with($p_photo, 'http')){
        $p_photo = $photo_url . $p_photo;
    }

    if(!isset($users_map[$uid])){
        $users_map[$uid] = [
            'user_id' => $uid,
            'name' => htmlspecialchars($row['name']),
            'profile_photo' => $p_photo,
            'stories' => []
        ];
    }
    
    // Fix Story Image
    $s_img = $row['image'];
    if($s_img && !str_starts_with($s_img, 'http')){
        $s_img = $story_url . $s_img;
    }
    
    $users_map[$uid]['stories'][] = [
        'id' => $row['story_id'],
        'image' => $s_img,
        'date' => $row['date']
    ];
}

// Convert map to array
$users_with_stories = array_values($users_map);

echo json_encode(["status" => "success", "data" => $users_with_stories]);
?>
