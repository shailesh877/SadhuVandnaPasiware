<?php
include("connection.php");

$call_id = $_POST['call_id'] ?? 0;
$status = $_POST['status'] ?? '';

if(!$call_id || !in_array($status, ['accepted', 'rejected', 'ended'])){
    echo "error";
    exit;
}

$stmt = $con->prepare("UPDATE tbl_calls SET status=? WHERE id=?");
$stmt->bind_param("si", $status, $call_id);
if($stmt->execute()){
    echo "ok";
} else {
    echo "error";
}
?>