<?php
session_start();
include("../connection.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login");
    exit;
}

date_default_timezone_set("Asia/Kolkata");

/* ============= ADD IMAGE ===============*/
if(isset($_POST["submit_data"])){

    $title = mysqli_real_escape_string($con, $_POST["title"]);
    $date = date("Y-m-d H:i:s");

    $image_name = "";

    if(!empty($_FILES["image"]["name"])){

        $folder = "../uploads/gallery/";
        if(!is_dir($folder)){
            mkdir($folder,0777,true);
        }

        $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $allow = ["jpg","jpeg","png","gif","webp"];

        if(in_array(strtolower($ext),$allow)){
            $newName = time().rand(1000,9999).".".$ext;
            if(move_uploaded_file($_FILES["image"]["tmp_name"], $folder.$newName)){
                $image_name = $newName;
            }
        }
    }

    if($image_name == ""){
        header("Location: admin_gallery.php?msg=".urlencode("Please upload image")."&type=error");
        exit;
    }

    mysqli_query($con,"INSERT INTO tbl_gallery (title,image,created_at) VALUES('$title','$image_name','$date')");
    header("Location: admin_gallery.php?msg=".urlencode("Image Added Successfully")."&type=success");
    exit;
}

/* ============= DELETE ===============*/
if(isset($_GET["delete"])){

    $id = intval($_GET["delete"]);
    $old = mysqli_fetch_assoc(mysqli_query($con,"SELECT image FROM tbl_gallery WHERE id='$id'"));
    if($old && $old['image']){
        $file = "../uploads/gallery/".$old['image'];
        if(file_exists($file)) unlink($file);
    }
    mysqli_query($con,"DELETE FROM tbl_gallery WHERE id='$id'");
    header("Location: admin_gallery.php?msg=".urlencode("Image Deleted")."&type=success");
    exit;
}

/* ============= FETCH ===============*/
$data = mysqli_query($con,"SELECT * FROM tbl_gallery ORDER BY id DESC");
?>

<?php include("header.php"); ?>



<main class="max-w-7xl mx-auto px-4 py-6 flex flex-col md:flex-row gap-8">

  <!-- ================= ADD FORM ================= -->
  <div class="w-full md:w-1/3">
    <div class="bg-white rounded-xl shadow-lg p-6">
        
      <?php
      if(isset($_GET['msg'])){
        $color = (isset($_GET['type']) && $_GET['type']=='success') ? 'green' : 'red';
        echo "<p class='mb-3 text-$color-600 font-semibold text-sm'>" . htmlspecialchars($_GET['msg']) . "</p>";
      }
      ?>

      <div class="flex items-center gap-2 mb-4">
        <i class="fa-solid fa-image text-orange-600"></i>
        <h2 class="text-lg font-bold">Add Gallery Image</h2>
      </div>

      <form method="POST" enctype="multipart/form-data" class="space-y-4">

        <!-- TITLE -->
        <div>
          <label class="block text-sm font-medium mb-1">Title</label>
          <input type="text" name="title" required
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm">
        </div>

        <!-- IMAGE -->
        <div>
          <label class="block text-sm font-medium mb-1">Upload Image</label>
          <input type="file" name="image" accept="image/*" required
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm">
          <p class="text-xs text-gray-500 mt-1">Recommended: JPG, PNG, WEBP</p>
        </div>

        <button name="submit_data"
          class="w-full bg-orange-500 hover:bg-orange-600 text-white py-2 rounded-lg font-semibold text-sm">
          Save Image
        </button>

      </form>
    </div>
  </div>

  <!-- ================= LIST VIEW ================= -->
  <div class="w-full md:w-2/3">
    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">

      <div class="p-2 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50">
        <div class="flex items-center gap-3">
          <a href="index" class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
            <i class="fa-solid fa-arrow-left text-orange-600 text-sm"></i>
          </a>
          <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
            Gallery Images
          </h2>
        </div>
        
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <div class="relative w-full sm:w-64">
            <i class="fa-solid fa-search absolute left-3 top-2.5 text-orange-400 text-sm"></i>
            <input 
              type="search" 
              id="searchInput" 
              placeholder="Search..." 
              class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 shadow-sm rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition text-sm text-gray-700"
            >
          </div>
          <div class="text-sm font-medium text-gray-500 bg-white px-3 py-1.5 rounded-lg border shadow-sm whitespace-nowrap">
            Total: <span class="text-orange-600 font-bold"><?= mysqli_num_rows($data) ?></span>
          </div>
        </div>
      </div>

      <div class="overflow-y-auto max-h-[525px]">
        <table class="w-full text-sm border-t border-gray-100">
          <thead class="bg-orange-500 text-white sticky top-0 z-10">
            <tr>
              <th class="px-4 py-2 text-left whitespace-nowrap">Sr No</th>
              <th class="px-4 py-2 text-left whitespace-nowrap">Thumbnail</th>
              <th class="px-4 py-2 text-left whitespace-nowrap">Title</th>
              <th class="px-4 py-2 text-left whitespace-nowrap">Date</th>
              <th class="px-4 py-2 text-center whitespace-nowrap">Action</th>
            </tr>
          </thead>

          <tbody class="divide-y bg-white">
          <?php $sr = 1; while($row = mysqli_fetch_assoc($data)) { ?>
            <tr class="hover:bg-orange-50">
              <!-- SR NO -->
              <td class="px-4 py-2 font-semibold"><?= $sr++ ?></td>

              <!-- IMAGE -->
              <td class="px-4 py-2">
                <?php if($row['image']!=""){ ?>
                  <img src="../uploads/gallery/<?= htmlspecialchars($row['image']) ?>" 
                       class="w-16 h-16 rounded object-cover border border-orange-200 shadow-sm">
                <?php } else { ?>
                  <span class="text-xs text-gray-400">No Image</span>
                <?php } ?>
              </td>

              <!-- TITLE -->
              <td class="px-4 py-2 font-medium">
                <?= htmlspecialchars($row['title']) ?>
              </td>

              <!-- DATE -->
              <td class="px-4 py-2 whitespace-nowrap text-gray-600">
                <?= date("d M Y", strtotime($row['created_at'])) ?>
              </td>

              <!-- ACTION -->
              <td class="px-4 py-2 text-center flex gap-2 justify-center">
                <a href="admin_gallery_edit.php?id=<?= $row['id'] ?>"
                  class="bg-blue-100 text-blue-600 w-8 h-8 flex items-center justify-center rounded">
                  <i class="fa-solid fa-pen"></i>
                </a>

                <a href="?delete=<?= $row['id'] ?>"
                  onclick="return confirm('Delete this image?')"
                  class="bg-red-100 text-red-600 w-8 h-8 flex items-center justify-center rounded">
                  <i class="fa-solid fa-trash"></i>
                </a>
              </td>
            </tr>
          <?php } ?>

          <?php if(mysqli_num_rows($data) == 0){ ?>
            <tr>
              <td colspan="5" class="text-center py-4 text-gray-500">
                No gallery images found.
              </td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>

</main>

</body>
<script>
document.getElementById("searchInput").addEventListener("input", function(){
  let v = this.value.toLowerCase();
  document.querySelectorAll("tbody tr").forEach(r => {
    if (r.children.length > 1) {
      r.style.display = r.innerText.toLowerCase().includes(v) ? "" : "none";
    }
  });
});
</script>
</html>
