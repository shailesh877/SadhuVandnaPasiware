<?php
include 'headers.php';
include 'connection.php';

$sql = "SELECT * FROM tbl_jobs_education ORDER BY id DESC";
$result = $con->query($sql);

$jobs = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $jobs]);
} else {
    echo json_encode(["status" => "success", "data" => []]);
}
?>
