<?php
include("connection.php");

header('Content-Type: application/json');

$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

$query = "SELECT * FROM music";

if (!empty($search)) {
    $query .= " WHERE title LIKE '%$search%' OR artist LIKE '%$search%' OR tags LIKE '%$search%'";
}

$query .= " ORDER BY id DESC LIMIT $limit";

$result = mysqli_query($con, $query);
$music_list = array();

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Ensure full URL for the audio file - pointing to the same root uploads folder
        $row['file_url'] = "https://www.sadhuvandna.co.in/uploads/music/" . $row['file_name'];
        $row['thumbnail'] = "https://www.sadhuvandna.co.in/images/music_default.png";
        $music_list[] = $row;
    }
    echo json_encode(array("status" => "success", "data" => $music_list));
} else {
    echo json_encode(array("status" => "error", "message" => mysqli_error($con)));
}
?>
