<?php
session_start();

// all sessions delete
session_unset();
session_destroy();

// Cookies delete 
setcookie('sadhu_user_id', '', time() - 3600, '/');
setcookie('sadhu_user_name', '', time() - 3600, '/');

// Redirect to login page
echo "<script>
alert('You have been logged out successfully.');
window.location.href = 'login';
</script>";
exit;
?>
