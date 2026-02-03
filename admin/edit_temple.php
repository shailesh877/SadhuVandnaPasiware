<?php
session_start();
include("../connection.php");

if (!isset($_SESSION['admin_id'])) {

    // Check If Cookie Exists
    if (isset($_COOKIE['sadhu_admin_id']) && isset($_COOKIE['sadhu_admin_token'])) {

        $id = $_COOKIE['sadhu_admin_id'];
        $token = $_COOKIE['sadhu_admin_token'];

        $q = mysqli_query($con, "SELECT * FROM tbl_admin WHERE admin_id='$id' LIMIT 1");

        if (mysqli_num_rows($q) == 1) {
            $row = mysqli_fetch_assoc($q);

            // Verify Cookie Token
            if (sha1($row['password']) === $token) {

                // Auto-Login using Cookie
                $_SESSION['admin_id'] = $row['admin_id'];
                $_SESSION['admin_name'] = $row['username'];

            }
        }
    }
}

// Still not logged in → redirect to login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login");
    exit;
}

date_default_timezone_set("Asia/Kolkata");

// ---------------- Fetch Temple ----------------
if (!isset($_GET['id'])) {
    die("Invalid Request");
}

$id = intval($_GET['id']);
$data = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM tbl_temple WHERE temple_id=$id"));

if (!$data) {
    die("Temple Not Found!");
}

// ---------------- Update Temple ----------------
if (isset($_POST['updateTemple'])) {

    $mahant_name = $_POST['mahant_name'];
    $mobile = $_POST['mobile'];
    $village = $_POST['village'];
    $taluka = $_POST['taluka'];
    $district = $_POST['district'];

    $final_photo = $data['photo'];

    // If new image uploaded
    if (!empty($_FILES['photo']['name'])) {

        $imgName = time() . "_" . $_FILES['photo']['name'];
        $target = "../uploads/temple/" . $imgName;

        move_uploaded_file($_FILES['photo']['tmp_name'], $target);

        // Delete old image
        if ($data['photo'] != "" && file_exists("../uploads/temple/" . $data['photo'])) {
            unlink("../uploads/temple/" . $data['photo']);
        }

        $final_photo = $imgName;
    }

    // Update Query
    mysqli_query($con, 
        "UPDATE tbl_temple SET 
            mahant_name='$mahant_name',
            mobile='$mobile',
            village='$village',
            taluka='$taluka',
            district='$district',
            photo='$final_photo'
         WHERE temple_id=$id"
    );

    echo "<script>alert('Temple Updated Successfully'); window.location='create_temple.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Edit Temple</title>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>

<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen">

<!-- Header -->
<header class="bg-white shadow-md sticky top-0 z-40">
    <div class="max-w-4xl mx-auto px-4 py-2 flex items-center">
        <a href="create_temple.php" class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
            <i class="fa-solid fa-arrow-left text-orange-600"></i>
        </a>
        <h1 class="text-xl font-bold text-orange-600 flex-1 text-center">Edit Temple</h1>
    </div>
</header>

<!-- Main -->
<main class="flex justify-center mt-6">
    <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-lg">

        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-2 gap-6">

            <!-- Image -->
            <div class="col-span-2 flex flex-col items-center">
                <div class="w-28 h-28 bg-gray-100 rounded-full border-2 border-dashed border-orange-300 overflow-hidden flex items-center justify-center">
                    <img id="previewImg" 
                         src="../uploads/temple/<?= $data['photo'] ?>" 
                         class="w-full h-full object-cover <?= $data['photo'] ? '' : 'hidden' ?>">
                    
                    <?php if (!$data['photo']) { ?>
                        <i id="defaultIcon" class="fa-solid fa-user-tie text-gray-300 text-3xl"></i>
                    <?php } ?>
                </div>

                <input type="file" name="photo" id="photo" class="hidden" accept="image/*" onchange="showPreview(event)">
                <label for="photo" class="px-4 py-1 bg-orange-500 text-white rounded-full text-xs cursor-pointer mt-2">
                    Change Photo
                </label>
            </div>

            <div>
                <label class="text-sm">Mahant Name *</label>
                <input type="text" name="mahant_name" value="<?= $data['mahant_name'] ?>" required class="w-full border px-3 py-2 rounded-lg">
            </div>

            <div>
                <label class="text-sm">Mobile *</label>
                <input type="text" name="mobile" value="<?= $data['mobile'] ?>" required class="w-full border px-3 py-2 rounded-lg">
            </div>

            <div>
                <label class="text-sm">Village *</label>
                <input type="text" name="village" value="<?= $data['village'] ?>" required class="w-full border px-3 py-2 rounded-lg">
            </div>

            <div>
                <label class="text-sm">Taluka *</label>
                <input type="text" name="taluka" value="<?= $data['taluka'] ?>" required class="w-full border px-3 py-2 rounded-lg">
            </div>

            <div>
                <label class="text-sm">District *</label>
                <input type="text" name="district" value="<?= $data['district'] ?>" required class="w-full border px-3 py-2 rounded-lg">
            </div>

            <div class="col-span-2 flex gap-4 mt-4 justify-center">
                <button type="submit" name="updateTemple" class="px-6 py-2 bg-orange-500 text-white rounded-lg">
                    <i class="fa-solid fa-check mr-1"></i> Update
                </button>

                <a href="create_temple.php" class="px-6 py-2 bg-gray-200 rounded-lg">
                    Cancel
                </a>
            </div>

        </form>

    </div>
</main>

<script>
function showPreview(event) {
    const file = event.target.files[0];
    if (!file) return;

    document.getElementById("previewImg").src = URL.createObjectURL(file);
    document.getElementById("previewImg").classList.remove("hidden");

    const icon = document.getElementById("defaultIcon");
    if (icon) icon.classList.add("hidden");
}
</script>

</body>
</html>
