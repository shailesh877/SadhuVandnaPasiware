<?php
include("connection.php");
session_start();

// Check if user is logged in
if(!isset($_SESSION['sadhu_user_id']) || empty($_SESSION['sadhu_user_id'])){
    if(isset($_COOKIE['sadhu_user_id']) && isset($_COOKIE['sadhu_user_name'])){
        $_SESSION['sadhu_user_id'] = $_COOKIE['sadhu_user_id'];
        $_SESSION['sadhu_user_name'] = $_COOKIE['sadhu_user_name'];
    } else {
        echo "<script>window.location.href='login';</script>";
        exit;
    }
}

$msg = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $current = trim($_POST['current_password']);
    $new = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    if(empty($current) || empty($new) || empty($confirm)){
        $msg = ['type'=>'error','text'=>'All fields are required!'];
    } elseif($new !== $confirm){
        $msg = ['type'=>'error','text'=>'New password and confirm password do not match!'];
    } else {
        // Fetch current password from DB
        $stmt = $con->prepare("SELECT password FROM tbl_members WHERE email=? LIMIT 1");
        $stmt->bind_param("i", $_SESSION['sadhu_user_id']);
        $stmt->execute();
        $res = $stmt->get_result();

        if($res->num_rows){
            $row = $res->fetch_assoc();
            if(password_verify($current, $row['password'])){
                // Update password
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $update = $con->prepare("UPDATE tbl_members SET password=? WHERE email=?");
                $update->bind_param("si", $hashed, $_SESSION['sadhu_user_id']);
                if($update->execute()){
                    $msg = ['type'=>'success','text'=>'Password changed successfully!'];
                } else {
                    $msg = ['type'=>'error','text'=>'Database error. Try again later.'];
                }
            } else {
                $msg = ['type'=>'error','text'=>'Current password is incorrect!'];
            }
        } else {
            $msg = ['type'=>'error','text'=>'User not found!'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password - Sadhu Vandana</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { font-family: 'Roboto', sans-serif; }
</style>
</head>
<body class="bg-white min-h-screen flex flex-col items-center justify-center p-4">

<div class="w-full max-w-md bg-white border border-orange-200 rounded-xl shadow-lg p-6">
    <h1 class="text-2xl font-bold text-orange-600 mb-4 text-center">Change Password</h1>

    <?php if($msg){ ?>
        <div class="mb-4 px-4 py-2 rounded <?php echo ($msg['type']=='success') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?= $msg['text'] ?>
        </div>
    <?php } ?>

    <form method="post" class="flex flex-col gap-4">
        <div>
            <label class="text-orange-700 font-medium">Current Password</label>
            <input type="password" name="current_password" required class="w-full border border-orange-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-200">
        </div>
        <div>
            <label class="text-orange-700 font-medium">New Password</label>
            <input type="password" name="new_password" required class="w-full border border-orange-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-200">
        </div>
        <div>
            <label class="text-orange-700 font-medium">Confirm Password</label>
            <input type="password" name="confirm_password" required class="w-full border border-orange-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-200">
        </div>
        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 rounded-lg flex items-center justify-center gap-2 transition">
            <i class="fa fa-lock"></i> Change Password
        </button>
    </form>

    <div class="mt-4 text-center">
        <a href="index" class="text-orange-600 hover:text-orange-700">← Back to Home</a>
    </div>
</div>

</body>
</html>
