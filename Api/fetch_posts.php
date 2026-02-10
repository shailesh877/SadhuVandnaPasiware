<?php
include('connection.php');

// Corrected query
$stmt = $pdo->prepare("\
    SELECT 
        p.*, 
        m.name, 
        m.profile_photo
    FROM tbl_posts p
    JOIN tbl_members m ON p.user_id = m.id AND m.status != 'Blocked'
    ORDER BY p.created_at DESC
");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add time ago
foreach ($posts as &$post) {
    $post['time_ago'] = timeAgo(strtotime($post['created_at']));
}

// Output JSON
echo json_encode($posts);

function timeAgo($time){
    $diff = time() - $time;
    if($diff < 60) return $diff.'s ago';
    elseif($diff < 3600) return floor($diff/60).'m ago';
    elseif($diff < 86400) return floor($diff/3600).'h ago';
    else return floor($diff/86400).'d ago';
}
?>
