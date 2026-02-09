<?php
include 'headers.php';
include 'connection.php';

$sender_id = $_GET['sender_id'] ?? ''; 
$receiver_id = $_GET['receiver_id'] ?? '';

// Fetch chat between two profiles
$query = "SELECT * FROM tbl_chats 
          WHERE (sender_id='$sender_id' AND receiver_id='$receiver_id') 
             OR (sender_id='$receiver_id' AND receiver_id='$sender_id') 
          ORDER BY id ASC";

$result = $con->query($query);
$chats = [];
while($row = $result->fetch_assoc()){
    $chats[] = $row;
}

echo json_encode(["status" => "success", "data" => $chats]);
?>
