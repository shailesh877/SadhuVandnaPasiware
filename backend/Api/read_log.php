<?php
if(file_exists("push_log.txt")) {
    echo nl2br(file_get_contents("push_log.txt"));
} else {
    echo "Log file not found.";
}
?>
