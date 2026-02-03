<?php
include 'headers.php';
include 'connection.php';

$sql = "SELECT * FROM tbl_branch ORDER BY id DESC";
$result = $con->query($sql);

$branches = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $branches]);
} else {
    echo json_encode(["status" => "success", "data" => []]);
}
?>
