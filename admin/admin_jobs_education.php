<?php
session_start();
include("../connection.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login");
    exit;
}

date_default_timezone_set("Asia/Kolkata");

/* ============= ADD DATA ===============*/
if(isset($_POST["submit_data"])){

    $type = mysqli_real_escape_string($con, $_POST["type"]);
    $title = mysqli_real_escape_string($con, $_POST["title"]);
    $description = mysqli_real_escape_string($con, $_POST["description"]);
    $date = date("Y-m-d H:i:s");

    /* IMAGE UPLOAD */
    $image_name = "";
    if(!empty($_FILES["image"]["name"])){

        $folder = "../uploads/jobs/";
        if(!is_dir($folder)){
            mkdir($folder,0777,true);
        }

        $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $allow = ["jpg","jpeg","png","gif","webp"];

        if(in_array(strtolower($ext),$allow)){
            $newName = time().rand(1000,9999).".".$ext;
            move_uploaded_file($_FILES["image"]["tmp_name"], $folder.$newName);
            $image_name = $newName;
        }
    }

    $sql = "INSERT INTO tbl_jobs_education (type, title, description, image, created_at)
            VALUES ('$type', '$title', '$description', '$image_name', '$date')";

    if(mysqli_query($con, $sql)){
        header("Location: admin_jobs_education.php?msg=".urlencode("Added Successfully")."&type=success");
    }else{
        header("Location: admin_jobs_education.php?msg=".urlencode("Error")."&type=error");
    }
}

/* ============= DELETE ================*/
if(isset($_GET["delete"])){

    $del_id = $_GET["delete"];
    
    // old image delete
    $old = mysqli_fetch_assoc(mysqli_query($con,"SELECT image FROM tbl_jobs_education WHERE id='$del_id'"));
    if($old && $old['image']!=""){
        $file = "../uploads/jobs/".$old['image'];
        if(file_exists($file)) unlink($file);
    }

    mysqli_query($con,"DELETE FROM tbl_jobs_education WHERE id='$del_id'");
    echo "<script>window.location='admin_jobs_education.php';</script>";
}

/* ============= FETCH ================*/
$data = mysqli_query($con, "SELECT * FROM tbl_jobs_education ORDER BY id DESC");
?>


<?php include("header.php"); ?>



<main class="max-w-7xl mx-auto px-4 py-6 flex flex-col md:flex-row gap-8">

  <!-- ================= ADD FORM ================= -->
  <div class="w-full md:w-1/3">
    <div class="bg-white rounded-xl shadow-lg p-6">
        
     <?php
if(isset($_GET['msg'])){
  $color = $_GET['type']=='success' ? 'green' : 'red';
  echo "<p class='text-$color-600 font-semibold'>" . htmlspecialchars($_GET['msg']) . "</p>";
}
?>


      <div class="flex items-center gap-2 mb-4">
        <i class="fa-solid fa-plus text-orange-600"></i>
        <h2 class="text-lg font-bold">Add Job / Education</h2>
      </div>

      <form method="POST" enctype="multipart/form-data" class="space-y-4">


        <!-- TYPE DROPDOWN -->
        <div>
          <label class="block text-sm font-medium mb-1">Select Type</label>
          <select name="type" required
            class="w-full border border-gray-300 rounded-lg px-4 py-2">
            <option value="">-- Choose --</option>
            <option value="job">Job</option>
            <option value="education">Education</option>
          </select>
        </div>

        <!-- TITLE -->
        <div>
          <label class="block text-sm font-medium mb-1">Title</label>
          <input type="text" name="title" required
            class="w-full border border-gray-300 rounded-lg px-4 py-2">
        </div>

        <!-- DESCRIPTION -->
        <div>
          <label class="block text-sm font-medium mb-1">Description</label>
          <textarea name="description" rows="4" required
            class="w-full border border-gray-300 rounded-lg px-4 py-2 resize-none"></textarea>
        </div>
        <!-- IMAGE -->
<div>
  <label class="block text-sm font-medium mb-1">Upload Image</label>
  <input type="file" name="image" accept="image/*"
    class="w-full border border-gray-300 rounded-lg px-4 py-2">
</div>

        <button name="submit_data"
          class="w-full bg-orange-500 hover:bg-orange-600 text-white py-2 rounded-lg font-semibold">
          Submit
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
        Jobs & Education
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
      <th class="px-4 py-2 text-left whitespace-nowrap">Type</th>
      <th class="px-4 py-2 text-left whitespace-nowrap">Thumbnail</th>
      <th class="px-4 py-2 text-left whitespace-nowrap">Title</th>
      <th class="px-4 py-2 text-left whitespace-nowrap">Description</th>
      <th class="px-4 py-2 text-left whitespace-nowrap">Date</th>
      <th class="px-4 py-2 text-center whitespace-nowrap">Action</th>
    </tr>
  </thead>

  <tbody class="divide-y bg-white">
  <?php $sr = 1; while($row = mysqli_fetch_assoc($data)) { ?>
    <tr class="hover:bg-orange-50">
      <!-- SR NO -->
      <td class="px-4 py-2 font-semibold"><?= $sr++ ?></td>

      <!-- TYPE -->
      <td class="px-4 py-2 capitalize">
        <?= htmlspecialchars($row['type']) ?>
      </td>
       <td class="">
        <?php if($row['image']!=""){ ?>
  <img src="../uploads/jobs/<?= $row['image'] ?>" 
       class="w-14 h-14 rounded object-cover border mt-1">
<?php } ?>

       </td>
      <!-- TITLE -->
      <td class="px-4 py-2 font-medium">
        <?= htmlspecialchars($row['title']) ?>
      </td>

      <!-- ✅ DESCRIPTION WITH READ MORE -->
      <td class="px-4 py-2 max-w-xs">
        <p class="line-clamp-1 text-gray-700 text-[13px]" id="desc-<?= $row['id'] ?>">
          <?= htmlspecialchars($row['description']) ?>
        </p>
        <button onclick="toggleDescription('desc-<?= $row['id'] ?>', this)"
          class="text-orange-600 text-xs font-semibold hover:underline mt-1">
          Read more
        </button>
      </td>

      <!-- DATE -->
      <td class="px-4 py-2 whitespace-nowrap">
        <?= date("d M Y", strtotime($row['created_at'])) ?>
      </td>

      <!-- ACTION -->
      <td class="px-4 py-2 text-center flex gap-2 justify-center">
        <a href="admin_jobs_education_edit.php?id=<?= $row['id'] ?>"
          class="bg-blue-100 text-blue-600 w-8 h-8 flex items-center justify-center rounded">
          <i class="fa-solid fa-edit"></i>
        </a>

        <a href="?delete=<?= $row['id'] ?>"
          onclick="return confirm('Delete this item?')"
          class="bg-red-100 text-red-600 w-8 h-8 flex items-center justify-center rounded">
          <i class="fa-solid fa-trash"></i>
        </a>
      </td>
    </tr>
  <?php } ?>

  <?php if(mysqli_num_rows($data) == 0){ ?>
    <tr>
      <td colspan="7" class="text-center py-4 text-gray-500">
        No data found
      </td>
    </tr>
  <?php } ?>
  </tbody>
</table>

  </div>
</div>
</div>

</main>
<script>
function toggleDescription(id, btn) {
  let el = document.getElementById(id);
  if (el.classList.contains('line-clamp-1')) {
    el.classList.remove('line-clamp-1');
    btn.innerText = "Read less";
  } else {
    el.classList.add('line-clamp-1');
    btn.innerText = "Read more";
  }
}

document.getElementById("searchInput").addEventListener("input", function(){
  let v = this.value.toLowerCase();
  document.querySelectorAll("tbody tr").forEach(r => {
    // Only search rows that are not the "No data found" row
    if (r.children.length > 1) {
      r.style.display = r.innerText.toLowerCase().includes(v) ? "" : "none";
    }
  });
});
</script>

</body>
</html>
