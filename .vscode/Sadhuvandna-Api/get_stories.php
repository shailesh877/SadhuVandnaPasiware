<?php
include 'headers.php';
include 'connection.php';

// Fetch users with active stories (last 24h)
// Logic similar to stories.php
$query = "
SELECT 
  m.id AS user_id,
  m.name,
  m.profile_photo,
  COUNT(s.id) AS total_stories,
  MAX(s.date) AS latest_date
FROM tbl_stories s
JOIN tbl_members m ON s.user_id = m.id
WHERE s.date > (NOW() - INTERVAL 1 DAY)
GROUP BY m.id
ORDER BY latest_date DESC
";

$result = $con->query($query);

$users_with_stories = [];
while($row = $result->fetch_assoc()){
    // Fetch actual stories for this user
    $uid = $row['user_id'];
    $s_query = "SELECT id, type, media, date FROM tbl_stories WHERE user_id='$uid' AND date > (NOW() - INTERVAL 1 DAY) ORDER BY date ASC";
    $s_res = $con->query($s_query);
    
    $stories = [];
    while($s = $s_res->fetch_assoc()){
        $stories[] = $s;
    }
    
    $row['stories'] = $stories;
    $users_with_stories[] = $row;
}

echo json_encode(["status" => "success", "data" => $users_with_stories]);
?>
