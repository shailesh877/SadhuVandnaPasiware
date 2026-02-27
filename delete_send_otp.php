<?php
session_start();
if (isset($_SESSION['sadhu_user_id'])) {
    if(isset($_GET['check_only'])){
        echo "allowed";
        exit;
    }
    echo "allowed";
} else {
    echo "invalid_session";
}
?>
