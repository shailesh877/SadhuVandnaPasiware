<?php
include 'headers.php';
include 'connection.php';

$sql = "SELECT * FROM tbl_temple ORDER BY temple_id DESC";
$result = $con->query($sql);

$temples = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $temples[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $temples]);
} else {
    echo json_encode(["status" => "success", "data" => []]);
}
?>
