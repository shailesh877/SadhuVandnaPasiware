<?php
include 'headers.php';
include 'connection.php';

$limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 20;
$cursor = isset($_REQUEST['cursor']) ? intval($_REQUEST['cursor']) : 0;

if ($cursor > 0) {
    $sql = "SELECT * FROM tbl_temple WHERE temple_id < $cursor ORDER BY temple_id DESC LIMIT $limit";
} else {
    $sql = "SELECT * FROM tbl_temple ORDER BY temple_id DESC LIMIT $limit";
}

$result = $con->query($sql);

$temples = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $temples[] = $row;
    }
}

$hasMore = count($temples) === $limit;
$nextCursor = ($hasMore && count($temples) > 0) ? $temples[count($temples) - 1]['temple_id'] : null;

echo json_encode([
    "status"      => "success",
    "data"        => $temples,
    "has_more"    => $hasMore,
    "next_cursor" => $nextCursor
]);
?>
