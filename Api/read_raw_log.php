<?php
header('Content-Type: text/plain');
if(file_exists("push_log.txt")) {
    echo file_get_contents("push_log.txt");
} else {
    echo "Log not found.";
}
?>
