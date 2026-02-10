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

// ------------ Fetch News Using ID -------------
if (!isset($_GET['id'])) {
    die("Invalid Request");
}
$id = intval($_GET['id']);

$query = mysqli_query($con, "SELECT * FROM tbl_news WHERE id='$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("News not found");
}

// ------------ Update News ---------------------
if (isset($_POST['update'])) {

    date_default_timezone_set("Asia/Kolkata");

    $title = mysqli_real_escape_string($con, $_POST['title']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $date = $_POST['date'];

    $old_images = $data['image']; // comma separated
    $final_images = $old_images;

    // ---- If New Images Selected ----
    if (!empty($_FILES['images']['name'][0])) {

        // Delete Old Images
        if (!empty($old_images)) {
            $imgs = explode(",", $old_images);
            foreach ($imgs as $img) {
                $path = "../uploads/news/" . $img;
                if (file_exists($path)) unlink($path);
            }
        }

        $new_images = [];
        $upload_path = "../uploads/news/";

        foreach ($_FILES['images']['name'] as $key => $name) {
            $tmp = $_FILES['images']['tmp_name'][$key];
            $new_name = time() . "_" . rand(1000,9999) . "_" . basename($name);

            if (move_uploaded_file($tmp, $upload_path . $new_name)) {
                $new_images[] = $new_name;
            }
        }

        $final_images = implode(",", $new_images);
    }

    // ---- Update Query ----
    $update = mysqli_query($con,
        "UPDATE tbl_news SET 
            title='$title',
            description='$description',
            created_at='$date',
            image='$final_images'
        WHERE id='$id'"
    );

    if ($update) {
        echo "<script>
                alert('News Updated Successfully!');
                window.location='add_views_news.php';
              </script>";
    } else {
        echo "<script>alert('Error while updating news');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Edit News - Admin Panel</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen">
  
<header class="bg-white shadow-md sticky top-0 z-40">
    <div class="max-w-6xl mx-auto px-4 py-2 flex items-center">
        <a href="add_views_news.php"
          class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition mr-2">
          <i class="fa-solid fa-arrow-left text-orange-600"></i>
        </a>
        <h1 class="text-xl font-bold text-orange-600 flex-1 text-center">Edit News</h1>
    </div>
</header>

<main class="flex-1 flex justify-center items-center py-4">
  <div class="bg-white rounded-xl shadow-lg p-8 max-w-xl w-full">

    <form method="POST" enctype="multipart/form-data">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Existing Images -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Current Images</label>

          <div class="flex flex-wrap gap-2 border rounded-lg p-2 bg-gray-50">
            <?php
            if (!empty($data['image'])) {
                $imgs = explode(",", $data['image']);
                foreach ($imgs as $img) {
                    echo "<img src='../uploads/news/$img' class='w-20 h-20 object-cover rounded-lg border'>";
                }
            }
            ?>
          </div>
        </div>

        <!-- Upload New Images -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Upload New Images</label>

          <input type="file" name="images[]" multiple accept="image/*"
            onchange="showPreview(event)"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm"/>

          <div id="newPreview" class="flex flex-wrap gap-2 mt-3"></div>
        </div>

        <!-- Title -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">News Title *</label>
          <input type="text" name="title" required
            value="<?= $data['title'] ?>"
            class="w-full px-4 py-3 border text-base border-gray-300 rounded-lg"/>
        </div>

        <!-- Description -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
          <textarea name="description" required rows="5"
            class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg"><?= $data['description'] ?></textarea>
        </div>

        <!-- Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
          <input type="date" name="date" required
            value="<?= date('Y-m-d', strtotime($data['created_at'])) ?>"
            class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg"/>
        </div>

        <!-- Buttons -->
        <div class="md:col-span-2 flex gap-4 mt-4">
          <button type="submit" name="update"
            class="px-8 py-3 text-sm bg-orange-500 hover:bg-orange-600 text-white rounded-lg shadow flex items-center gap-2">
            <i class="fa-solid fa-check"></i> Update News
          </button>

          <a href="add_views_news.php"
            class="px-8 py-3 text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg flex items-center gap-2">
            <i class="fa-solid fa-xmark"></i> Cancel
          </a>
        </div>

      </div>
    </form>
  </div>
</main>

<script>
function showPreview(event){
  const box = document.getElementById("newPreview");
  box.innerHTML = "";

  Array.from(event.target.files).forEach(file => {
    const reader = new FileReader();
    reader.onload = function(){
      const img = document.createElement("img");
      img.src = reader.result;
      img.className = "w-20 h-20 object-cover rounded-lg border";
      box.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}
</script>

</body>
</html>
