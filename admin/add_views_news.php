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

$message = "";

/* -----------------------------
   INSERT NEWS (MULTIPLE IMAGE)
------------------------------ */
if(isset($_POST["submit_news"])){

    $title = mysqli_real_escape_string($con, $_POST["title"]);
    $description = mysqli_real_escape_string($con, $_POST["description"]);

    $image_names = [];

    if(!empty($_FILES["images"]["name"][0])){

        $target_path = "../uploads/news/";
        if(!is_dir($target_path)){
            mkdir($target_path, 0777, true);
        }

        foreach($_FILES["images"]["name"] as $key => $name){
            $tmp = $_FILES["images"]["tmp_name"][$key];

            $new_name = time() . "_" . rand(1000,9999) . "_" . basename($name);
            move_uploaded_file($tmp, $target_path . $new_name);

            $image_names[] = $new_name;
        }
    }

    $images = implode(",", $image_names);
    $date = date("Y-m-d H:i:s");

    $sql = "INSERT INTO tbl_news (title, description, image, created_at)
            VALUES ('$title', '$description', '$images', '$date')";

    if(mysqli_query($con, $sql)){
        $message = "<p class='text-green-600 font-semibold'>News Added Successfully!</p>";
    } else {
        $message = "<p class='text-red-600 font-semibold'>ERROR!</p>";
    }
}

/* -----------------------------
   DELETE NEWS (ALL IMAGES)
------------------------------ */
if(isset($_GET["delete"])){
    $del_id = $_GET["delete"];

    $img = mysqli_fetch_assoc(mysqli_query($con, "SELECT image FROM tbl_news WHERE id='$del_id'"));
    if($img && $img["image"]){
        $imgs = explode(",", $img["image"]);
        foreach($imgs as $pic){
            $path = "../uploads/news/" . $pic;
            if(file_exists($path)){ unlink($path); }
        }
    }

    mysqli_query($con, "DELETE FROM tbl_news WHERE id='$del_id'");
    echo "<script>window.location='add_views_news.php';</script>";
}

/* -----------------------------
   FETCH ALL NEWS
------------------------------ */
$fetch_news = mysqli_query($con, "SELECT * FROM tbl_news ORDER BY id DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<title>Sadhu Vandana - News Management</title>

<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

<style>
.truncate-line {
  display: -webkit-box !important;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}
.overflow-y-auto::-webkit-scrollbar {
  width: 0px;
  display: none;
}
</style>

<script>
function toggleDescription(id, btn){
  const p = document.getElementById(id);
  if(p.classList.contains("truncate-line")){
    p.classList.remove("truncate-line");
    btn.textContent = "see less";
  } else {
    p.classList.add("truncate-line");
    btn.textContent = "see more";
  }
}

// ✅ MULTIPLE IMAGE PREVIEW
function previewImages(event){
  const previewBox = document.getElementById("previewBox");
  previewBox.innerHTML = "";

  Array.from(event.target.files).forEach(file => {
    const reader = new FileReader();
    reader.onload = function(){
      const img = document.createElement("img");
      img.src = reader.result;
      img.className = "w-24 h-24 object-cover rounded-lg border";
      previewBox.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}
</script>
</head>

<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen">

<header class="bg-white shadow-md sticky top-0 z-40">
  <div class="max-w-6xl mx-auto px-4 py-2 flex items-center">
    <a href="index"
      class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition mr-2">
      <i class="fa-solid fa-arrow-left text-orange-600"></i>
    </a>
    <h1 class="text-xl font-bold text-orange-600 flex-1 text-center">News Management</h1>
  </div>
</header>

<main class="max-w-8xl mx-auto px-4 py-4 flex flex-col md:flex-row gap-8">

<!-- RIGHT: ADD NEWS -->
<div class="w-full md:max-w-md md:w-1/3 md:order-2">
  <div class="bg-white rounded-xl shadow-lg p-6 mb-4">

    <?= $message ?>

    <h2 class="text-xl font-bold text-gray-800 mb-6">Add New News</h2>

    <form method="POST" enctype="multipart/form-data">

      <label class="block text-sm font-medium mb-2">News Images</label>

      <div id="previewBox"
        class="w-full min-h-[120px] bg-gray-100 rounded-lg border-2 border-dashed border-gray-300 flex flex-wrap gap-2 p-2 mb-3">
        <i class="fa-solid fa-image text-gray-400 text-3xl"></i>
      </div>

      <input type="file" name="images[]" multiple required
        accept="image/*"
        onchange="previewImages(event)"
        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg mb-4"/>

      <input type="text" name="title" required
        placeholder="Enter news title"
        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg mb-4"/>

      <textarea name="description" required rows="5"
        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg mb-4 resize-none"
        placeholder="Enter news description"></textarea>

      <button name="submit_news" type="submit"
        class="w-full px-6 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-lg shadow">
        Submit News
      </button>

    </form>
  </div>
</div>

<!-- LEFT: NEWS LIST -->
<div class="w-full md:w-2/3 md:order-1">
  <div class="bg-white rounded-xl shadow-lg p-6">

    <h2 class="text-xl font-bold text-gray-800 mb-6">All News</h2>

    <div class="overflow-y-auto h-[525px]">

      <table class="w-full">
        <thead class="bg-orange-500 text-white sticky top-0">
          <tr>
            <th class="px-4 py-3">ID</th>
            <th class="px-4 py-3">Images</th>
            <th class="px-4 py-3">Title</th>
            <th class="px-4 py-3">Description</th>
            <th class="px-4 py-3">Date</th>
            <th class="px-4 py-3 text-center">Actions</th>
          </tr>
        </thead>

        <tbody>
        <?php $i=1; while($row = mysqli_fetch_assoc($fetch_news)){ ?>
          <tr class="border-b hover:bg-orange-50">

            <td class="px-4 py-3">#<?= $row["id"] ?></td>

            <td class="px-4 py-3">
              <?php
              $imgs = explode(",", $row["image"]);
              foreach($imgs as $img){
              ?>
              <img src="../uploads/news/<?= $img ?>" class="w-12 h-12 rounded inline-block mr-1 mb-1">
              <?php } ?>
            </td>

            <td class="px-4 py-3"><?= $row["title"] ?></td>

            <td class="px-4 py-3 max-w-xs">
              <p class="truncate-line text-sm" id="desc-<?= $i ?>">
                <?= $row["description"] ?>
              </p>
              <button onclick="toggleDescription('desc-<?= $i ?>', this)"
                class="text-xs text-orange-600 hover:underline">see more</button>
            </td>

            <td class="px-4 py-3 text-sm">
              <?= date("d M Y, h:i A", strtotime($row["created_at"])) ?>
            </td>

            <td class="px-4 py-3 text-center">
              <a href="admin_news_edit?id=<?= $row['id'] ?>"
                class="w-8 h-8 inline-flex items-center justify-center bg-blue-100 hover:bg-blue-200 text-blue-600 rounded-lg">
                <i class="fa-solid fa-edit"></i>
              </a>

              <a href="?delete=<?= $row['id'] ?>"
                onclick="return confirm('Delete this news?');"
                class="w-8 h-8 inline-flex items-center justify-center bg-red-100 hover:bg-red-200 text-red-600 rounded-lg ml-2">
                <i class="fa-solid fa-trash"></i>
              </a>
            </td>

          </tr>
        <?php $i++; } ?>
        </tbody>
      </table>

    </div>

  </div>
</div>
</main>

</body>
</html>
