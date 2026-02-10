<?php
$base_dir = __DIR__;
if (!file_exists($base_dir . '/connection.php')) {
    die("Error: connection.php not found in $base_dir");
}
include($base_dir . '/connection.php');
header('Content-Type: application/json');

echo "DEBUGGING PHOTOS...\n";

// 1. Check raw photos in DB
$sql = "SELECT id, full_name, photo, user_id FROM tbl_marriage_profiles ORDER BY id DESC LIMIT 5";
$res = $con->query($sql);

$profiles = [];
while($row = $res->fetch_assoc()){
    $profiles[] = $row;
}
echo json_encode($profiles, JSON_PRETTY_PRINT);

echo "\n\nCHECKING ACTIVE CHATS FOR USER ID [Assume ID=1 or similar]...\n";
// I don't know the exact user ID, but I can guess or just check raw DB mostly.
?>
