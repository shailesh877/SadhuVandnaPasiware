<?php
session_start();
include("../connection.php");

if (!isset($_SESSION['admin_id'])) {
    if (isset($_COOKIE['sadhu_admin_id']) && isset($_COOKIE['sadhu_admin_token'])) {
        $id = $_COOKIE['sadhu_admin_id'];
        $token = $_COOKIE['sadhu_admin_token'];
        $q = mysqli_query($con, "SELECT * FROM tbl_admin WHERE admin_id='$id' LIMIT 1");
        if (mysqli_num_rows($q) == 1) {
            $row = mysqli_fetch_assoc($q);
            if (sha1($row['password']) === $token) {
                $_SESSION['admin_id'] = $row['admin_id'];
                $_SESSION['admin_name'] = $row['username'];
            }
        }
    }
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login");
    exit;
}

date_default_timezone_set("Asia/Kolkata");

// ---------------- FETCH BRANCH ------------------
if (!isset($_GET['id'])) {
    die("Invalid Request!");
}
$id = intval($_GET['id']);
$q = mysqli_query($con, "SELECT * FROM tbl_branch WHERE id='$id'");
$data = mysqli_fetch_assoc($q);
if (!$data) die("Branch Not Found!");

$errors = [];

// ---------------- UPDATE BRANCH -------------------
if (isset($_POST['update'])) {
    $village = trim($_POST['village']);
    $branch_name = trim($_POST['branch_name']);
    $mahant_name = trim($_POST['mahant_name']);
    $mahant_mobile = trim($_POST['mahant_mobile']);
    $details = trim($_POST['details']);

    if ($village=="" || $branch_name=="" || $mahant_name=="" || $mahant_mobile=="" || $details=="") {
        $errors[] = "All fields are required.";
    }

    $final_image = $data['photo'];

    if (!empty($_FILES['photo']['name'])) {
        $allowed = ['image/jpeg','image/jpg','image/png'];
        $file_type = $_FILES['photo']['type'];
        $file_tmp = $_FILES['photo']['tmp_name'];
        $file_size = $_FILES['photo']['size'];
        $orig_name = basename($_FILES['photo']['name']);

        if (!in_array($file_type,$allowed)) $errors[]="Only JPG, JPEG, PNG allowed.";
        elseif ($file_size>2*1024*1024) $errors[]="Image must be less than 2MB.";
        else {
            $new_name = time().'_'.preg_replace('/[^A-Za-z0-9_\-\.]/','_',$orig_name);
            $upload_dir = "../uploads/branches/";
            if(!is_dir($upload_dir)) mkdir($upload_dir,0777,true);
            if(move_uploaded_file($file_tmp,$upload_dir.$new_name)){
                if($data['photo']!="" && file_exists($upload_dir.$data['photo'])) unlink($upload_dir.$data['photo']);
                $final_image=$new_name;
            }
        }
    }

    if(empty($errors)){
        $sql="UPDATE tbl_branch SET 
              branch_village='".mysqli_real_escape_string($con,$village)."',
              branch_name='".mysqli_real_escape_string($con,$branch_name)."',
              mahant_name='".mysqli_real_escape_string($con,$mahant_name)."',
              mahant_mobile='".mysqli_real_escape_string($con,$mahant_mobile)."',
              details='".mysqli_real_escape_string($con,$details)."',
              photo='$final_image'
              WHERE id='$id'";

        if(mysqli_query($con,$sql)){
            echo "<script>alert('Branch Updated Successfully!');window.location='create_branch.php';</script>";
            exit;
        } else {
            $errors[]="Database Error! ".mysqli_error($con);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Branch</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
function previewImage(input){
    if(input.files && input.files[0]){
        document.getElementById('preview').src = URL.createObjectURL(input.files[0]);
    }
}
</script>
</head>
<body class="bg-orange-50 min-h-screen">

<header class="bg-white shadow-md sticky top-0 z-40">
    <div class="max-w-5xl mx-auto px-4 py-2 flex items-center">
        <a href="create_branch.php" class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition mr-2">
            <i class="fa-solid fa-arrow-left text-orange-600"></i>
        </a>
        <h1 class="text-xl font-bold text-orange-600 flex-1 text-center">Edit Branch</h1>
    </div>
</header>

<main class="flex justify-center items-center py-6">
    <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-xl">

        <?php if(!empty($errors)){ ?>
            <div class="bg-red-100 border border-red-300 text-red-700 p-3 rounded mb-4">
                <ul><?php foreach($errors as $e) echo "<li>$e</li>"; ?></ul>
            </div>
        <?php } ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">

            <!-- Image -->
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">Mahant Photo</label>
                <div class="flex items-center gap-4">
                    <img id="preview" src="../uploads/branches/<?= $data['photo'] ?>" class="w-20 h-20 object-cover rounded-full border">
                    <input type="file" name="photo" accept="image/*" class="border px-3 py-2 rounded-lg text-sm" onchange="previewImage(this)">
                </div>
            </div>

            <!-- Branch Village -->
            <div>
                <label class="text-sm font-medium text-gray-700">Branch Village *</label>
                <input type="text" name="village" value="<?= htmlspecialchars($data['branch_village']) ?>" class="w-full text-sm px-4 py-2 border rounded-lg">
            </div>

            <!-- Branch Name -->
            <div>
                <label class="text-sm font-medium text-gray-700">Branch Name *</label>
                <input type="text" name="branch_name" value="<?= htmlspecialchars($data['branch_name']) ?>" class="w-full text-sm px-4 py-2 border rounded-lg">
            </div>

            <!-- Mahant Name -->
            <div>
                <label class="text-sm font-medium text-gray-700">Mahant Name *</label>
                <input type="text" name="mahant_name" value="<?= htmlspecialchars($data['mahant_name']) ?>" class="w-full text-sm px-4 py-2 border rounded-lg">
            </div>

            <!-- Mahant Mobile -->
            <div>
                <label class="text-sm font-medium text-gray-700">Mahant Mobile *</label>
                <input type="text" name="mahant_mobile" value="<?= htmlspecialchars($data['mahant_mobile']) ?>" class="w-full text-sm px-4 py-2 border rounded-lg">
            </div>

            <!-- Details -->
            <div>
                <label class="text-sm font-medium text-gray-700">Branch History / Details *</label>
                <textarea name="details" rows="4" class="w-full text-sm px-4 py-2 border rounded-lg"><?= htmlspecialchars($data['details']) ?></textarea>
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 pt-3">
                <button type="submit" name="update" class="px-6 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg shadow text-sm">
                    <i class="fa-solid fa-check"></i> Update Branch
                </button>
                <a href="create_branch.php" class="px-6 py-2 bg-gray-200 text-gray-800 rounded-lg text-sm">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </a>
            </div>

        </form>
    </div>
</main>

</body>
</html>
