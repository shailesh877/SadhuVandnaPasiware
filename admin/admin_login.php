<?php
session_start();
include("../connection.php");

$error = "";

// --- Already Logged In ---
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// --- Remember Me Auto Login ---
if (isset($_COOKIE['sadhu_admin_id']) && isset($_COOKIE['sadhu_admin_token'])) {

    $cid = $_COOKIE['sadhu_admin_id'];
    $ctoken = $_COOKIE['sadhu_admin_token'];

    $q = mysqli_query($con, "SELECT * FROM tbl_admin WHERE admin_id='$cid' LIMIT 1");

    if (mysqli_num_rows($q) == 1) {
        $row = mysqli_fetch_assoc($q);

        if ($ctoken == sha1($row['password'])) {

            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['admin_name'] = $row['username'];

            header("Location: index.php");
            exit;
        }
    }
}


// ================= LOGIN SUBMIT =================
if (isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $q = mysqli_query($con, "SELECT * FROM tbl_admin WHERE username='$username' LIMIT 1");

    if (mysqli_num_rows($q) == 1) {

        $row = mysqli_fetch_assoc($q);

        if (password_verify($password, $row['password'])) {

            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['admin_name'] = $row['username'];

            if ($remember) {
                setcookie("sadhu_admin_id", $row['admin_id'], time() + (86400 * 7), "/");
                setcookie("sadhu_admin_token", sha1($row['password']), time() + (86400 * 7), "/");
            }

            header("Location: index.php");
            exit;

        } else {
            $error = "❌ Wrong Password!";
        }

    } else {
        $error = "❌ Username Not Found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

</head>

<body class="bg-gradient-to-br from-orange-100 to-orange-200 min-h-screen flex items-center justify-center px-4">

    <div class="bg-white/80 backdrop-blur-xl border border-orange-200 shadow-2xl rounded-2xl p-10 w-full max-w-md animate-fadeIn">

        <div class="text-center mb-6">
            <div class="w-20 h-20 bg-orange-500 rounded-2xl mx-auto flex items-center justify-center shadow-lg">
                <i class="fa-solid fa-user-shield text-white text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-orange-700 mt-4">Admin Login</h1>
            <p class="text-gray-500 text-sm">Secure Panel Access</p>
        </div>

        <?php if (!empty($error)) { ?>
            <div class="bg-red-100 border border-red-300 text-red-700 py-2 px-4 mb-4 rounded-lg text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
            </div>
        <?php } ?>

        <form method="POST" class="space-y-5">

            <!-- Username -->
            <div>
                <label class="text-sm font-semibold text-gray-700">Username</label>
                <div class="flex items-center border rounded-lg px-3 py-2 bg-white shadow-sm">
                    <i class="fa-solid fa-user text-orange-500 mr-2"></i>
                    <input type="text" name="username" required
                           class="flex-1 outline-none text-sm bg-transparent">
                </div>
            </div>

            <!-- Password -->
            <div>
                <label class="text-sm font-semibold text-gray-700">Password</label>
                <div class="flex items-center border rounded-lg px-3 py-2 bg-white shadow-sm">
                    <i class="fa-solid fa-lock text-orange-500 mr-2"></i>
                    <input type="password" name="password" required
                           class="flex-1 outline-none text-sm bg-transparent">
                </div>
            </div>

            <!-- Remember Me -->
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" class="w-4 h-4">
                Remember Me
            </label>

            <!-- Login Button -->
            <button type="submit" name="login"
                class="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white py-2.5 rounded-lg font-semibold shadow-lg transition-all">
                <i class="fa-solid fa-right-to-bracket mr-2"></i> Login
            </button>

        </form>

        <!-- Footer -->
        <p class="text-center text-xs text-gray-500 mt-6">
            © <?= date("Y") ?> Admin Panel. All Rights Reserved.
        </p>

    </div>

</body>
</html>
