<?php
session_start();
include("../connection.php");

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matrimony_profile_fee'])) {
		$fee_raw = trim($_POST['matrimony_profile_fee']);
		// Allow numbers and dot only
		$fee = preg_replace('/[^0-9.]/', '', $fee_raw);

		if ($fee === '') {
				$_SESSION['msg'] = "Please enter a valid fee.";
				header('Location: admin_setting');
				exit;
		}

		$key = 'matrimony_profile_fee';
		$k = mysqli_real_escape_string($con, $key);
		$v = mysqli_real_escape_string($con, $fee);

		$check = mysqli_query($con, "SELECT id FROM tbl_settings WHERE `key`='" . $k . "' LIMIT 1");
		if ($check && mysqli_num_rows($check) > 0) {
				mysqli_query($con, "UPDATE tbl_settings SET `value`='" . $v . "', updated_at=NOW() WHERE `key`='" . $k . "'");
		} else {
				mysqli_query($con, "INSERT INTO tbl_settings (`key`,`value`) VALUES ('" . $k . "','" . $v . "')");
		}

		$_SESSION['msg'] = "Matrimony profile fee updated successfully.";
		header('Location: admin_setting');
		exit;
}

// Ensure default exists and fetch current value
$key = 'matrimony_profile_fee';
$k = mysqli_real_escape_string($con, $key);
$res = mysqli_query($con, "SELECT `value` FROM tbl_settings WHERE `key`='" . $k . "' LIMIT 1");
$fee = '';
if ($res && mysqli_num_rows($res) > 0) {
		$row = mysqli_fetch_assoc($res);
		$fee = $row['value'];
} else {
		// insert default 0 if missing
		mysqli_query($con, "INSERT INTO tbl_settings (`key`,`value`) VALUES ('" . $k . "','0')");
		$fee = '0';
}
?>

<?php include("header.php"); ?>



<main class="max-w-3xl mx-auto py-8 px-4">

<?php if(isset($_SESSION['msg'])): ?>
<div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg shadow-sm border-l-4 border-green-500 font-medium">
	<?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
</div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden w-full max-w-lg mx-auto">
    <div class="p-4 border-b border-gray-100 flex items-center gap-3 bg-gray-50">
      <a href="index.php" class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
        <i class="fa-solid fa-arrow-left text-orange-600 text-sm"></i>
      </a>
      <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
        <i class="fa-solid fa-gear text-orange-600"></i>
        Site Settings
      </h2>
    </div>

    <div class="p-6">
	<h2 class="text-lg font-semibold mb-4 text-gray-700">Matrimony Profile Fee</h2>

	<form method="post" class="space-y-4">
		<div>
			<label class="block text-sm text-gray-600 mb-1">Fee (in your currency)</label>
			<input name="matrimony_profile_fee" type="number" step="0.01" min="0" value="<?= htmlspecialchars($fee) ?>" class="w-full border rounded-lg px-4 py-2" />
		</div>

		<div class="flex items-center gap-3">
			<button class="bg-orange-600 text-white px-4 py-2 rounded">Save</button>
			<a href="index" class="text-sm text-gray-500">Cancel</a>
		</div>
	</form>
    </div>
</div>

</main>

</body>
</html>

