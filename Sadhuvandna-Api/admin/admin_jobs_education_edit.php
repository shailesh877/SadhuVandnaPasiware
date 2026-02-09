<?php
session_start();
include("../connection.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login");
    exit;
}

// ------------ Fetch Data Using ID -------------
if (!isset($_GET['id'])) {
    die("Invalid Request");
}
$id = intval($_GET['id']);

$query = mysqli_query($con, "SELECT * FROM tbl_jobs_education WHERE id='$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("Record not found");
}

// ------------ Update Data ---------------------
if (isset($_POST['update'])) {

    date_default_timezone_set("Asia/Kolkata");

    $type = mysqli_real_escape_string($con, $_POST['type']);
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $description = mysqli_real_escape_string($con, $_POST['description']);

    $image_name = $data['image']; // default old

    // ========= IMAGE UPLOAD =========
    if (!empty($_FILES["image"]["name"])) {

        $folder = "../uploads/jobs/";
        if(!is_dir($folder)) mkdir($folder,0777,true);

        $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $allow = ["jpg","jpeg","png","gif","webp"];

        if(in_array(strtolower($ext),$allow)){

            // delete old image
            if($image_name!="" && file_exists("../uploads/jobs/".$image_name)){
                unlink("../uploads/jobs/".$image_name);
            }

            $newName = time().rand(1000,9999).".".$ext;
            move_uploaded_file($_FILES["image"]["tmp_name"], $folder.$newName);
            $image_name = $newName;
        }
    }

    $update = mysqli_query($con,
        "UPDATE tbl_jobs_education SET 
            type='$type',
            title='$title',
            description='$description',
            image='$image_name'
        WHERE id='$id'"
    );

    if ($update) {
        echo "<script>alert('Updated Successfully!');window.location='admin_jobs_education.php';</script>";
    } else {
        echo "<script>alert('Error while updating data');</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Job / Education</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen">

<!-- HEADER -->
<header class="bg-white shadow-md sticky top-0 z-40">
  <div class="max-w-6xl mx-auto flex items-center px-4 py-2">
    <a href="admin_jobs_education.php"
      class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-2">
      <i class="fa-solid fa-arrow-left text-orange-600"></i>
    </a>
    <h1 class="text-xl font-bold text-orange-600 flex-1 text-center">
      Edit Job / Education
    </h1>
  </div>
</header>

<!-- MAIN -->
<main class="flex items-center justify-center py-6 px-4">
  <div class="bg-white rounded-xl shadow-lg p-8 max-w-xl w-full">

   <form method="POST" enctype="multipart/form-data">

      <div class="space-y-5">

        <!-- TYPE -->
        <div>
          <label class="block text-sm font-medium mb-1">Select Type</label>
          <select name="type" required
            class="w-full border border-gray-300 rounded-lg px-4 py-2">

            <option value="job" <?= $data['type']=='job' ? 'selected' : '' ?>>Job</option>
            <option value="education" <?= $data['type']=='education' ? 'selected' : '' ?>>Education</option>

          </select>
        </div>

        <!-- TITLE -->
        <div>
          <label class="block text-sm font-medium mb-1">Title</label>
          <input type="text" name="title" required
            value="<?= htmlspecialchars($data['title']) ?>"
            class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>

        <!-- DESCRIPTION -->
        <div>
          <label class="block text-sm font-medium mb-1">Description</label>
          <textarea name="description" rows="5" required
            class="w-full border border-gray-300 rounded-lg px-4 py-2 resize-none"><?= htmlspecialchars($data['description']) ?></textarea>
        </div>
           <!-- IMAGE -->
    <div>
      <label class="block text-sm font-medium mb-1">Image</label>

      <?php if($data['image']!=""){ ?>
        <img src="../uploads/jobs/<?= $data['image'] ?>" class="w-24 h-24 object-cover rounded mb-2 border">
      <?php } ?>

      <input type="file" name="image" accept="image/*"
        class="w-full border border-gray-300 rounded-lg px-4 py-2">
      <p class="text-xs text-gray-500 mt-1">Leave empty to keep previous image</p>
    </div>
        <!-- BUTTONS -->
        <div class="flex gap-4 pt-4">
          <button type="submit" name="update"
            class="flex-1 bg-orange-500 hover:bg-orange-600 text-white py-2 rounded-lg font-semibold flex items-center justify-center gap-2">
            <i class="fa-solid fa-check"></i> Update
          </button>

          <a href="admin_jobs_education.php"
            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 rounded-lg font-semibold flex items-center justify-center gap-2">
            <i class="fa-solid fa-xmark"></i> Cancel
          </a>
        </div>

      </div>
    </form>

  </div>
</main>

</body>
</html>
