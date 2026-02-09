<?php
include("connection.php");
date_default_timezone_set("Asia/Kolkata");

// sirf har page load pe ek hi baar per minute chale
$now = time();
$last_run = $_SESSION['last_auto_delete'] ?? 0;
if ($now - $last_run > 60) { // har 1 minute me ek baar max chale
    $_SESSION['last_auto_delete'] = $now;

    $cutoff = date("Y-m-d H:i:s", strtotime("-24 hours"));
    $query = $con->query("SELECT id, media FROM tbl_stories WHERE date < '$cutoff'");
    while($s = $query->fetch_assoc()){
        if(file_exists($s['media'])) unlink($s['media']);
        $con->query("DELETE FROM tbl_stories WHERE id=" . $s['id']);
    }
}
?>
