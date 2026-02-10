<?php
session_start();
include("../connection.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login");
    exit;
}

// ---- Fetch Record ----
if (!isset($_GET['id'])) {
    die("Invalid Request");
}
$id = intval($_GET['id']);

$q = mysqli_query($con, "SELECT * FROM tbl_gallery WHERE id='$id' LIMIT 1");
$data = mysqli_fetch_assoc($q);

if (!$data) {
    die("Record not found");
}

// ---- UPDATE ----
if (isset($_POST['update'])) {

    $title = mysqli_real_escape_string($con, $_POST['title']);

    // image update
    $image_name = $data['image']; // old image

    if (!empty($_FILES['image']['name'])) {

        $folder = "../uploads/gallery/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $allow = ["jpg","jpeg","png","gif","webp"];

        if (in_array(strtolower($ext), $allow)) {

            $newName = "G_".time().rand(1000,9999).".".$ext;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $folder.$newName)) {

                // delete old
                if ($image_name && file_exists($folder.$image_name)) unlink($folder.$image_name);

                $image_name = $newName;
            }
        }
    }

    mysqli_query($con,
        "UPDATE tbl_gallery SET 
            title='$title',
            image='$image_name'
        WHERE id='$id'"
    );

    header("Location: admin_gallery.php?msg=".urlencode("Updated Successfully")."&type=success");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Gallery Image</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen">

<!-- HEADER -->
<header class="bg-white shadow-md sticky top-0 z-40">
  <div class="max-w-6xl mx-auto flex items-center px-4 py-2">
    <a href="admin_gallery.php"
      class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-2">
      <i class="fa-solid fa-arrow-left text-orange-600"></i>
    </a>
    <h1 class="text-xl font-bold text-orange-600 flex-1 text-center">
      Edit Gallery Image
    </h1>
  </div>
</header>


<!-- MAIN -->
<main class="flex items-center justify-center py-8 px-4">
 <div class="bg-white rounded-xl shadow-lg p-8 max-w-xl w-full">

    <div class="flex items-center gap-2 mb-4">
      <i class="fa-solid fa-image text-orange-600"></i>
      <h2 class="text-lg font-bold">Update Image</h2>
    </div>

   <form method="POST" enctype="multipart/form-data"
         class="space-y-5">

      <!-- Title -->
      <div>
        <label class="block text-sm font-medium mb-1">Title</label>
        <input type="text" name="title"
         value="<?= htmlspecialchars($data['title']) ?>"
         required
         class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm">
      </div>

      <!-- OLD IMAGE -->
      <div>
        <label class="block text-sm font-medium mb-2">Current Image</label>
        <img src="../uploads/gallery/<?= $data['image'] ?>"
         class="w-48 h-48 object-cover rounded border border-orange-200 shadow-sm">
      </div>

      <!-- New -->
      <div>
        <label class="block text-sm font-medium mb-1">Replace Image</label>
        <input type="file" name="image" accept="image/*"
         class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm">
        <p class="text-xs text-gray-500">Leave blank to keep old image</p>
      </div>

      <!-- Buttons -->
      <div class="flex gap-4 pt-4">
        <button type="submit" name="update"
          class="flex-1 bg-orange-500 hover:bg-orange-600 
                 text-white py-2 rounded-lg font-semibold flex items-center justify-center gap-2">
          <i class="fa-solid fa-check"></i> Update
        </button>

        <a href="admin_gallery.php"
          class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 rounded-lg 
                 font-semibold flex items-center justify-center gap-2">
          <i class="fa-solid fa-xmark"></i> Cancel
        </a>
      </div>

   </form>
 </div>
</main>

</body>
</html>
