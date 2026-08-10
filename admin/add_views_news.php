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
<?php include("header.php"); ?>



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
  <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">

  <div class="p-2 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50">
    <div class="flex items-center gap-3">
      <a href="index" class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
        <i class="fa-solid fa-arrow-left text-orange-600 text-sm"></i>
      </a>
      <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
        All News
      </h2>
    </div>
    
    <div class="flex items-center gap-2 w-full sm:w-auto">
      <div class="relative w-full sm:w-64">
        <i class="fa-solid fa-search absolute left-3 top-2.5 text-orange-400 text-sm"></i>
        <input 
          type="search" 
          id="searchInput" 
          placeholder="Search news..." 
          class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 shadow-sm rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition text-sm text-gray-700"
        >
      </div>
      <div class="text-sm font-medium text-gray-500 bg-white px-3 py-1.5 rounded-lg border shadow-sm whitespace-nowrap">
        Total: <span class="text-orange-600 font-bold"><?= mysqli_num_rows($fetch_news) ?></span>
      </div>
    </div>
  </div>

    <div class="overflow-y-auto h-[525px]">

      <table class="w-full">
        <thead class="bg-orange-500 text-white sticky top-0">
          <tr>
            <th class="px-4 py-3 whitespace-nowrap">Sr No</th>
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

            <td class="px-4 py-3 font-semibold"><?= $i ?></td>

            <td class="px-4 py-3">
              <?php
              $imgs = explode(",", $row["image"]);
              foreach($imgs as $img){
                  $path = "../uploads/news/" . $img;
              ?>
              <img src="<?= $path ?>" onclick="openImageModal('<?= $path ?>')" class="w-12 h-12 object-cover rounded inline-block mr-1 mb-1 cursor-pointer hover:opacity-80 transition shadow-sm border border-gray-200">
              <?php } ?>
            </td>

            <td class="px-4 py-3"><?= $row["title"] ?></td>

            <td class="px-4 py-3 max-w-xs">
              <p class="line-clamp-1 text-sm" id="desc-<?= $i ?>">
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

<!-- IMAGE PREVIEW MODAL -->
<div id="imagePreviewModal" class="fixed inset-0 z-[100] bg-black/90 hidden flex items-center justify-center p-4 backdrop-blur-sm transition-opacity">
  <button onclick="closeImageModal()" class="absolute top-4 right-4 md:top-6 md:right-6 text-white hover:text-orange-500 bg-white/10 hover:bg-white/20 w-10 h-10 rounded-full flex items-center justify-center transition-all z-10">
    <i class="fa-solid fa-xmark text-xl"></i>
  </button>
  <div class="max-w-4xl w-full max-h-[90vh] flex items-center justify-center relative">
    <img id="modalPreviewImage" src="" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl">
  </div>
</div>

</body>
<script>
document.getElementById("searchInput").addEventListener("input", function(){
  let v = this.value.toLowerCase();
  document.querySelectorAll("tbody tr").forEach(r => {
    r.style.display = r.innerText.toLowerCase().includes(v) ? "" : "none";
  });
});

function toggleDescription(id, btn) {
  let el = document.getElementById(id);
  if (el.classList.contains('line-clamp-1')) {
    el.classList.remove('line-clamp-1');
    btn.innerText = "see less";
  } else {
    el.classList.add('line-clamp-1');
    btn.innerText = "see more";
  }
}

function openImageModal(src) {
  document.getElementById('modalPreviewImage').src = src;
  document.getElementById('imagePreviewModal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeImageModal() {
  document.getElementById('imagePreviewModal').classList.add('hidden');
  document.getElementById('modalPreviewImage').src = "";
  document.body.style.overflow = '';
}

// Close modal on escape key or clicking outside
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeImageModal();
});
document.getElementById('imagePreviewModal').addEventListener('click', function(e) {
  if (e.target === this) closeImageModal();
});
</script>
</html>
