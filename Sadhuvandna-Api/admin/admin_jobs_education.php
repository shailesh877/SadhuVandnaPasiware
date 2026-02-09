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


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Jobs & Education Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<style>
.desc-text{
  max-height: 3.2rem;     /* approx 2 lines */
  overflow: hidden;
  transition: max-height 0.3s ease;
}
</style>

</head>

<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen">

<header class="bg-white shadow-md sticky top-0 z-40">
  <div class="max-w-6xl mx-auto flex items-center px-4 py-2">
    <a href="index"
      class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-2">
      <i class="fa-solid fa-arrow-left text-orange-600"></i>
    </a>
    <h1 class="text-xl font-bold text-orange-600 flex-1 text-center">
      Jobs & Education Management
    </h1>
  </div>
</header>

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
<div class="overflow-x-auto max-h-[500px] relative">

<table class="min-w-full text-sm border border-orange-200">
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
        <p class="desc-text text-gray-700 text-[13px]" id="desc-<?= $row['id'] ?>">
          <?= htmlspecialchars($row['description']) ?>
        </p>
        <button onclick="toggleDesc('desc-<?= $row['id'] ?>', this)"
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
      <td colspan="6" class="text-center py-4 text-gray-500">
        No data found
      </td>
    </tr>
  <?php } ?>
  </tbody>
</table>

</div>


</main>
<script>
function toggleDesc(id, btn){
  const el = document.getElementById(id);
  if(el.style.maxHeight && el.style.maxHeight !== "3.2rem"){
    el.style.maxHeight = "3.2rem";
    btn.innerText = "Read more";
  }else{
    el.style.maxHeight = el.scrollHeight + "px";
    btn.innerText = "Read less";
  }
}
</script>

</body>
</html>
