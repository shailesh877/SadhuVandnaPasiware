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

<?php include("header.php"); ?>

<main class="max-w-3xl mx-auto py-8 px-4">
    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden w-full max-w-lg mx-auto">
        <div class="p-4 border-b border-gray-100 flex items-center gap-3 bg-gray-50">
            <a href="index.php" class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
                <i class="fa-solid fa-arrow-left text-orange-600 text-sm"></i>
            </a>
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-lock-open text-orange-600"></i>
                Change Password
            </h2>
        </div>
        <div class="p-6">

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
    </div>
</main>

</body>

</html>
