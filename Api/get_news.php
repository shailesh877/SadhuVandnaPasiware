<?php
include 'headers.php';
include 'connection.php';

$query = "SELECT * FROM tbl_news ORDER BY id DESC";
$result = $con->query($query);

$news = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // App expects 'images' field. In DB it might be comma separated 'image' column?
        // php/news.php used: $images = array_filter(explode(",", $row['image']));
        // Let's standarize to 'images' array in response.
        $imgs = [];
        if(!empty($row['image'])){
             $imgs = array_values(array_filter(explode(",", $row['image'])));
        }
        $row['images'] = $imgs; // Add array field
        $news[] = $row;
    }
}

echo json_encode(["status" => "success", "data" => $news]);
?>
