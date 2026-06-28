<?php
include 'headers.php';
include 'connection.php';

// Auto-migration: Ensure News Anchor job exists in tbl_jobs_education
$check_anchor = $con->query("SELECT id FROM tbl_jobs_education WHERE id = 9999");
if ($check_anchor && $check_anchor->num_rows == 0) {
    $con->query("INSERT INTO tbl_jobs_education (id, type, title, description, image, created_at) VALUES (9999, 'job', 'News Anchor', 'Apply for News Anchor. We are looking for talented news anchors to join our team.', '', NOW())");
}

// Auto-migration: Ensure category column exists in tbl_news
$check_column = $con->query("SHOW COLUMNS FROM tbl_news LIKE 'category'");
if ($check_column && $check_column->num_rows == 0) {
    $con->query("ALTER TABLE tbl_news ADD COLUMN category VARCHAR(100) DEFAULT 'ताज़ा खबर'");
}

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
