<?php
include 'connection.php';

$res = $con->query("DESCRIBE tbl_members last_active");
$row = $res->fetch_assoc();

$res2 = $con->query("SELECT id, last_active, NOW(), (last_active >= NOW() - INTERVAL 5 MINUTE) as is_online FROM tbl_members ORDER BY last_active DESC LIMIT 3");
$data = [];
while($r = $res2->fetch_assoc()){
    $data[] = $r;
}

echo json_encode([
    "column" => $row,
    "data" => $data
]);
?>
