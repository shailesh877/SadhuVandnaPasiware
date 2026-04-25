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

// Fetch Top 5 Latest News for Ticker
$tickerQuery = "SELECT title FROM tbl_news ORDER BY id DESC LIMIT 5";
$tickerResult = $con->query($tickerQuery);
$tickerData = [];
if ($tickerResult && $tickerResult->num_rows > 0) {
    while ($row = $tickerResult->fetch_assoc()) {
        $tickerData[] = $row['title'];
    }
}

echo json_encode(["status" => "success", "data" => $news, "ticker" => $tickerData]);
?>
