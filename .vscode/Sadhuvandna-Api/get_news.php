<?php
include 'headers.php';
include 'connection.php';

// Fetch all news ordered by latest
$query = "SELECT id, title, description, image, created_at FROM tbl_news ORDER BY id DESC";
$result = $con->query($query);

$news = [];
while($row = $result->fetch_assoc()){
    // Handle multiple images
    $images = [];
    if(!empty($row['image'])){
        $images = array_filter(explode(",", $row['image']));
        // Prepend base URL for images if needed, or handle in app
        // Assuming images are in ../php/uploads/news/ relative to this file
        // But API is in Sadhuvandna-Api/, images are in php/uploads/news/
        // Path: ../php/uploads/news/
        // URL: http://.../bangosambadApp/php/uploads/news/
    }
    
    $row['images'] = array_values($images);
    unset($row['image']); // Remove raw string
    $news[] = $row;
}

echo json_encode(["status" => "success", "data" => $news]);
?>
