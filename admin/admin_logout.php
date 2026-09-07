<?php
session_start();
session_destroy();

// Delete Cookies
setcookie("sadhu_admin_id", "", time() - 3600, "/");
setcookie("sadhu_admin_token", "", time() - 3600, "/");

header("Location: admin_login.php");
exit;
?>
