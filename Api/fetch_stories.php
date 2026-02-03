<?php
// Api/fetch_stories.php
require_once("connection.php");
date_default_timezone_set('Asia/Kolkata');

$user_id = intval($_GET['user_id'] ?? 0); // The viewer's ID

// Helper to get full URL
// Assuming images are hosted at root/uploads/stories...
// We will return relative path from DB and App will prepend BASE_URL/../ if needed, or we return full URL.
// Let's return the path as is from DB (e.g. 'uploads/stories/xyz.jpg')

$response = [];

// 1. Fetch My Stories
$my_stories = [];
if ($user_id) {
    $q = $con->query("SELECT id, media, type, date, (SELECT COUNT(*) FROM tbl_story_views v WHERE v.story_id = s.id) as views FROM tbl_stories s WHERE user_id='$user_id' AND date > (NOW() - INTERVAL 1 DAY) ORDER BY date ASC");
    while ($r = $q->fetch_assoc()) {
        $my_stories[] = $r;
    }
}

// 2. Fetch Others' Stories grouped by User
// We want users who have stories in the last 24h.
// Prioritize unseen users? 
// Logic mirroring stories.php:
$sql = "
SELECT 
  m.id AS user_id,
  m.name,
  m.profile_photo,
  MAX(s.date) AS latest_date,
  (SELECT COUNT(*) FROM tbl_stories s2 WHERE s2.user_id = m.id AND s2.date > (NOW() - INTERVAL 1 DAY)) as total_stories,
  SUM(CASE WHEN (SELECT COUNT(*) FROM tbl_story_views v WHERE v.story_id = s.id AND v.viewer_id = '$user_id') = 0 THEN 1 ELSE 0 END) AS unseen_count
FROM tbl_stories s
JOIN tbl_members m ON s.user_id = m.id
WHERE s.date > (NOW() - INTERVAL 1 DAY) AND m.id != '$user_id' AND m.status != 'Blocked'
GROUP BY m.id
ORDER BY latest_date DESC
";

$others = [];
$res = $con->query($sql);
while ($row = $res->fetch_assoc()) {
    // Fetch stories for this user
    $uid = $row['user_id'];
    $s_q = $con->query("SELECT id, media, type, date, (SELECT COUNT(*) FROM tbl_story_views v WHERE v.story_id = s.id AND v.viewer_id = '$user_id') as seen FROM tbl_stories s WHERE user_id='$uid' AND date > (NOW() - INTERVAL 1 DAY) ORDER BY date ASC");
    $user_stories = [];
    while ($s = $s_q->fetch_assoc()) {
        $s['seen'] = $s['seen'] > 0; // Convert to boolean
        $user_stories[] = $s;
    }
    
    $row['stories'] = $user_stories;
    $others[] = $row;
}

echo json_encode(['status' => 'success', 'my_stories' => $my_stories, 'others' => $others]);
?>
