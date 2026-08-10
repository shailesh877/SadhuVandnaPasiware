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

<?php include("header.php"); ?>

<!-- MAIN -->
<main class="max-w-3xl mx-auto py-8 px-4">
 <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
    
    <div class="p-4 border-b border-gray-100 flex items-center gap-3 bg-gray-50">
      <a href="admin_gallery.php" class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
        <i class="fa-solid fa-arrow-left text-orange-600 text-sm"></i>
      </a>
      <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
        <i class="fa-solid fa-image text-orange-600"></i>
        Edit Gallery Image
      </h2>
    </div>

    <div class="p-6">
       <form method="POST" enctype="multipart/form-data" class="space-y-5">

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

      </div>
   </form>
   </div>
 </div>
</main>

</body>
</html>
