<?php
include 'headers.php';
include 'connection.php';

$limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 20;
$cursor = isset($_REQUEST['cursor']) ? intval($_REQUEST['cursor']) : 0;

if ($cursor > 0) {
    $sql = "SELECT * FROM tbl_jobs_education WHERE id < $cursor ORDER BY id DESC LIMIT $limit";
} else {
    $sql = "SELECT * FROM tbl_jobs_education ORDER BY id DESC LIMIT $limit";
}

$result = $con->query($sql);

$jobs = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
}

$hasMore = count($jobs) === $limit;
$nextCursor = ($hasMore && count($jobs) > 0) ? $jobs[count($jobs) - 1]['id'] : null;

echo json_encode([
    "status"      => "success",
    "data"        => $jobs,
    "has_more"    => $hasMore,
    "next_cursor" => $nextCursor
]);
?>
