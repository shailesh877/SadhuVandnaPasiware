<?php
session_start();
include("../connection.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$success = "";
$error = "";

if (isset($_POST['changePass'])) {

    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $id = $_SESSION['admin_id'];

    $q = mysqli_query($con, "SELECT * FROM tbl_admin WHERE admin_id='$id'");
    $row = mysqli_fetch_assoc($q);

    if (!password_verify($old, $row['password'])) {
        $error = "❌ Old Password is incorrect!";
    } elseif ($new != $confirm) {
        $error = "⚠️ New passwords do not match!";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        mysqli_query($con, "UPDATE tbl_admin SET password='$hash' WHERE admin_id='$id'");
        $success = "✅ Password updated successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen flex justify-center items-center px-4">

    <div class="bg-white/80 backdrop-blur shadow-2xl rounded-2xl p-8 w-full max-w-lg border border-orange-200 transition-all">

        <!-- Header with Back Button -->
        <div class="flex items-center mb-6">
            <a href="index.php" 
               class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center hover:bg-orange-200 transition">
                <i class="fa-solid fa-arrow-left text-orange-600"></i>
            </a>

            <h1 class="flex-1 text-center text-2xl font-bold text-orange-600">
                Change Password
            </h1>
        </div>

        <!-- Messages -->
        <?php if ($error) { ?>
            <div class="bg-red-100 border border-red-300 text-red-700 py-2 px-4 rounded-lg text-sm mb-4">
                <?= $error ?>
            </div>
        <?php } ?>

        <?php if ($success) { ?>
            <div class="bg-green-100 border border-green-300 text-green-700 py-2 px-4 rounded-lg text-sm mb-4">
                <?= $success ?>
            </div>
        <?php } ?>

        <!-- Form -->
        <form method="POST" class="space-y-5">

            <div>
                <label class="text-sm font-semibold text-gray-700">Old Password</label>
                <div class="flex items-center border rounded-lg px-3 py-2 bg-white">
                    <i class="fa-solid fa-lock text-orange-500 mr-2"></i>
                    <input type="password" name="old_password" required
                        class="flex-1 outline-none text-sm">
                </div>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">New Password</label>
                <div class="flex items-center border rounded-lg px-3 py-2 bg-white">
                    <i class="fa-solid fa-key text-orange-500 mr-2"></i>
                    <input type="password" name="new_password" required
                        class="flex-1 outline-none text-sm">
                </div>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Confirm Password</label>
                <div class="flex items-center border rounded-lg px-3 py-2 bg-white">
                    <i class="fa-solid fa-check text-orange-500 mr-2"></i>
                    <input type="password" name="confirm_password" required
                        class="flex-1 outline-none text-sm">
                </div>
            </div>

            <!-- Update Button -->
            <button type="submit" name="changePass"
                class="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white py-2.5 rounded-lg font-semibold shadow-lg transition">
                <i class="fa-solid fa-rotate-right mr-2"></i> Update Password
            </button>

        </form>

    </div>

</body>

</html>
