<?php
include("connection.php");
header('Content-Type: application/json');

$res = $con->query("SELECT id, name, fcm_token FROM tbl_members WHERE fcm_token != '' LIMIT 10");
$tokens = [];
while($row = $res->fetch_assoc()) {
    $tokens[] = $row;
}

echo json_encode([
    "total_with_tokens" => $res->num_rows,
    "samples" => $tokens
]);
?>
