<?php
include 'headers.php';
include 'connection.php';

$query = "SELECT * FROM tbl_news ORDER BY id DESC";
$result = $con->query($query);

$news = [];
while ($row = $result->fetch_assoc()) {
    // Handle multiple images if comma separated
    $row['images'] = !empty($row['image']) ? explode(',', $row['image']) : [];
    $news[] = $row;
}

echo json_encode(["status" => "success", "data" => $news]);
?>
