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

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Admin - Settings</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen">

<!-- HEADER -->
<header class="bg-white shadow sticky top-0 z-40">
	<div class="max-w-7xl mx-auto px-4 py-4 space-y-3">
		<div class="flex items-center justify-between">
			<div class="flex items-center gap-3">
				<a href="index" class="w-9 h-9 flex items-center justify-center bg-orange-100 rounded-lg">
					<i class="fa-solid fa-arrow-left text-orange-600"></i>
				</a>
				<h1 class="text-xl font-bold">Site Settings</h1>
			</div>
		</div>
	</div>
</header>

<main class="max-w-3xl mx-auto px-4 py-8">

<?php if(isset($_SESSION['msg'])): ?>
<div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
	<?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
</div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow p-6">
	<h2 class="text-lg font-semibold mb-4">Matrimony Profile Fee</h2>

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

</main>

</body>
</html>

