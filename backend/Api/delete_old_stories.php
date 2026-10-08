<?php
include("connection.php");

// get all old stories
$old_stories = $con->query("SELECT media FROM tbl_stories WHERE date < (NOW() - INTERVAL 1 DAY)");

while($s = $old_stories->fetch_assoc()) {
    $media = trim($s['media']);
    if(!$media) continue;

    $file_path = __DIR__ . "/uploads/stories/" . basename($media);

    if(file_exists($file_path)) {
        unlink($file_path);
    }
}

// delete from database
$con->query("DELETE FROM tbl_stories WHERE date < (NOW() - INTERVAL 1 DAY)");


?>
