<?php
include 'headers.php';
include 'connection.php';

$user_id = intval($_REQUEST['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid user."]);
    exit;
}

$query = "SELECT * FROM tbl_breaking_news WHERE poster_id = $user_id ORDER BY id DESC";
$result = $con->query($query);

$news = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $news[] = $row;
    }
}

echo json_encode(["status" => "success", "data" => $news]);
?>
