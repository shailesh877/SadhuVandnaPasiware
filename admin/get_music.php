<?php
include("../connection.php");

$res = mysqli_query($con, "SELECT * FROM music ORDER BY id DESC");

$data = [];
while($row = mysqli_fetch_assoc($res)){
    $data[] = $row;
}

echo json_encode($data);
