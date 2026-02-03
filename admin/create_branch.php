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
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_branch'])) {
    $village = trim($_POST['village'] ?? '');
    $branch_name = trim($_POST['branch_name'] ?? '');
    $mahant_name = trim($_POST['mahant_name'] ?? '');
    $mahant_mobile = trim($_POST['mahant_mobile'] ?? '');
    $details = trim($_POST['details'] ?? '');

    if($village=='') $errors[]="Branch village required.";
    if($branch_name=='') $errors[]="Branch name required.";
    if($mahant_name=='') $errors[]="Mahant name required.";
    if($mahant_mobile=='') $errors[]="Mahant mobile required.";
    if($details=='') $errors[]="Details required.";

    $image_name = "";
    if(!empty($_FILES['photo']['name'])){
        $file=$_FILES['photo'];
        $allowed_types=["image/jpeg","image/jpg","image/png"];
        if(!in_array($file['type'],$allowed_types)) $errors[]="Only JPG/PNG allowed!";
        elseif($file['size']>2*1024*1024) $errors[]="Image must be under 2MB!";
        else{
            $image_name = time().'_'.preg_replace("/[^A-Za-z0-9\._-]/","_",$file['name']);
            $uploadDir="../uploads/branches/";
            if(!is_dir($uploadDir)) mkdir($uploadDir,0777,true);
            move_uploaded_file($file['tmp_name'],$uploadDir.$image_name);
        }
    }

    if(empty($errors)){
        $village=mysqli_real_escape_string($con,$village);
        $branch_name=mysqli_real_escape_string($con,$branch_name);
        $mahant_name=mysqli_real_escape_string($con,$mahant_name);
        $mahant_mobile=mysqli_real_escape_string($con,$mahant_mobile);
        $details=mysqli_real_escape_string($con,$details);
        $created_at=date("Y-m-d H:i:s");

        $sql="INSERT INTO tbl_branch 
              (branch_village,branch_name,mahant_name,mahant_mobile,details,photo,created_at)
              VALUES ('$village','$branch_name','$mahant_name','$mahant_mobile','$details','$image_name','$created_at')";
        if(mysqli_query($con,$sql)) {
            header("Location: create_branch.php?success=1");
            exit;
        } else {
            $errors[]="Database error!";
        }
    }
}

if(isset($_GET['delete'])){
    $id=intval($_GET['delete']);
    $q=mysqli_query($con,"SELECT photo FROM tbl_branch WHERE id=$id");
    $r=mysqli_fetch_assoc($q);
    if(!empty($r['photo']) && file_exists("../uploads/branches/".$r['photo'])){
        unlink("../uploads/branches/".$r['photo']);
    }
    mysqli_query($con,"DELETE FROM tbl_branch WHERE id=$id");
    header("Location: create_branch.php");
    exit;
}

$branches=mysqli_query($con,"SELECT * FROM tbl_branch ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Branch Create & View</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
<script>
function toggleDetail(id, btn){
  let el=document.getElementById(id);
  if(el.classList.contains("line-clamp-1")){
    el.classList.remove("line-clamp-1");
    btn.textContent="See less";
  } else {
    el.classList.add("line-clamp-1");
    btn.textContent="See more";
  }
}

function previewBranchPhoto(input){
  const box=document.getElementById("branch-photo-preview");
  if(input.files && input.files[0]){
    let reader=new FileReader();
    reader.onload=e=>{
      box.innerHTML='<img src="'+e.target.result+'" class="w-16 h-16 rounded-full object-cover"/>';
    };
    reader.readAsDataURL(input.files[0]);
  } else {
    box.innerHTML='<i class="fa-solid fa-user-tie text-gray-300 text-xl"></i>';
  }
}
</script>
</head>
<body class="bg-orange-50 min-h-screen">

<!-- Header -->
<header class="bg-white shadow-md sticky top-0 z-40">
  <div class="max-w-6xl mx-auto px-4 py-2 flex items-center">
    <a href="index" class="mr-2 w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition">
      <i class="fa-solid fa-arrow-left text-orange-600"></i>
    </a>
    <h1 class="text-xl font-bold text-orange-600 flex-1 text-center">Add / View Branch</h1>
  </div>
</header>

<main class="flex gap-5 max-w-6xl mx-auto p-4 mt-4">

  <!-- LEFT FORM -->
  <div class="w-full md:w-2/5">
    <div class="bg-white p-6 rounded-xl shadow-lg">
      <h2 class="text-lg font-bold text-orange-600 mb-4"><i class="fa-solid fa-code-branch text-orange-500"></i> Create Branch</h2>

      <?php if(isset($_GET['success'])): ?>
        <p class="text-green-600 font-semibold mb-2">Branch created successfully.</p>
      <?php endif; ?>
      <?php if(!empty($errors)): ?>
        <div class="text-red-600 mb-3"><?php foreach($errors as $e) echo "• $e<br>"; ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="md:col-span-2 flex flex-col items-center">
          <div id="branch-photo-preview" class="w-16 h-16 border-2 border-dashed border-orange-300 bg-gray-100 rounded-full flex items-center justify-center mb-2">
            <i class="fa-solid fa-user-tie text-gray-300 text-xl"></i>
          </div>
          <input type="file" name="photo" id="photoInput" class="hidden" accept="image/*" onchange="previewBranchPhoto(this)">
          <label for="photoInput" class="px-3 py-1 bg-orange-500 text-white rounded-full text-xs cursor-pointer">Mahant Photo</label>
          <span class="text-xs text-gray-400">JPG/PNG Max 2MB</span>
        </div>

        <div>
          <label class="text-sm font-medium">Village *</label>
          <input type="text" name="village" required class="w-full px-3 py-2 border rounded">
        </div>

        <div>
          <label class="text-sm font-medium">Branch Name *</label>
          <input type="text" name="branch_name" required class="w-full px-3 py-2 border rounded">
        </div>

        <div>
          <label class="text-sm font-medium">Mahant Name *</label>
          <input type="text" name="mahant_name" required class="w-full px-3 py-2 border rounded">
        </div>

        <div>
          <label class="text-sm font-medium">Mahant Mobile *</label>
          <input type="text" name="mahant_mobile" required class="w-full px-3 py-2 border rounded">
        </div>

        <div class="md:col-span-2">
          <label class="text-sm font-medium">Details *</label>
          <textarea name="details" rows="3" required class="w-full px-3 py-2 border rounded"></textarea>
        </div>

        <div class="md:col-span-2 flex gap-3">
          <button name="submit_branch" class="bg-orange-500 text-white px-6 py-2 rounded">Submit</button>
          <button type="reset" onclick="document.getElementById('branch-photo-preview').innerHTML='<i class=\'fa-solid fa-user-tie text-gray-300 text-xl\'></i>'" class="bg-gray-200 px-6 py-2 rounded">Reset</button>
        </div>

      </form>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden mt-4 space-y-4">
      <h2 class="text-lg font-semibold text-orange-600 mb-3">Branch List</h2>
      <?php mysqli_data_seek($branches,0); $i=1; while($row=mysqli_fetch_assoc($branches)): ?>
      <div class="bg-white border rounded-xl p-4 shadow flex gap-4">
        <div>
          <?php if($row['photo'] && file_exists("../uploads/branches/".$row['photo'])): ?>
            <img src="../uploads/branches/<?= $row['photo'] ?>" class="w-16 h-16 rounded-full object-cover">
          <?php else: ?>
            <i class="fa-solid fa-user-tie text-gray-300 text-xl"></i>
          <?php endif; ?>
        </div>
        <div class="flex-1">
          <div class="font-bold"><?= $row['branch_name'] ?></div>
          <div class="text-xs"><b>Mahant:</b> <?= $row['mahant_name'] ?></div>
          <div class="text-xs"><b>Village:</b> <?= $row['branch_village'] ?></div>
          <div class="text-xs"><b>Mobile:</b> <?= $row['mahant_mobile'] ?></div>
          <span id="mobile-detail-<?= $i ?>" class="text-xs line-clamp-1"><?= $row['details'] ?></span>
          <button onclick="toggleDetail('mobile-detail-<?= $i ?>',this)" class="text-orange-600 text-xs">See more</button>

          <div class="flex gap-2 mt-2">
            <a href="branch_edit?id=<?= $row['id'] ?>" class="w-7 h-7 bg-blue-100 text-blue-600 flex items-center justify-center rounded"><i class="fa fa-edit"></i></a>
            <a href="create_branch.php?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this branch?')" class="w-7 h-7 bg-red-100 text-red-600 flex items-center justify-center rounded"><i class="fa fa-trash"></i></a>
          </div>
        </div>
      </div>
      <?php $i++; endwhile; ?>
    </div>
  </div>

  <!-- Desktop Table -->
  <div class="hidden md:block w-3/5">
    <div class="bg-white rounded-xl shadow p-6">
      <h2 class="text-lg font-semibold text-orange-600 mb-3">Branch List</h2>
      <div class="h-[500px] overflow-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-orange-500 text-white sticky top-0">
            <tr>
              <th class="px-3 py-2">Photo</th>
              <th class="px-3 py-2">Branch</th>
              <th class="px-3 py-2">Mahant</th>
              <th class="px-3 py-2">Village</th>
              <th class="px-3 py-2">Mobile</th>
              <th class="px-3 py-2">Details</th>
              <th class="px-3 py-2 text-center">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y">
          <?php mysqli_data_seek($branches,0); while($row=mysqli_fetch_assoc($branches)): ?>
            <tr>
              <td class="px-3 py-2 text-center">
                <?php if($row['photo'] && file_exists("../uploads/branches/".$row['photo'])): ?>
                  <img src="../uploads/branches/<?= $row['photo'] ?>" class="w-10 h-10 rounded-full object-cover mx-auto">
                <?php else: ?>
                  <i class="fa-solid fa-user-tie text-gray-300"></i>
                <?php endif; ?>
              </td>
              <td class="px-3 py-2"><?= $row['branch_name'] ?></td>
              <td class="px-3 py-2"><?= $row['mahant_name'] ?></td>
              <td class="px-3 py-2"><?= $row['branch_village'] ?></td>
              <td class="px-3 py-2"><?= $row['mahant_mobile'] ?></td>
              <td class="px-3 py-2">
                <span id="desk-detail-<?= $row['id'] ?>" class="line-clamp-1"><?= $row['details'] ?></span>
                <button onclick="toggleDetail('desk-detail-<?= $row['id'] ?>',this)" class="text-orange-600 text-xs">See more</button>
              </td>
              <td class="px-3 py-2 text-center">
                <a href="branch_edit?id=<?= $row['id'] ?>" class="w-7 h-7 bg-blue-100 text-blue-600 rounded flex items-center justify-center inline-block"><i class="fa fa-edit"></i></a>
                <a href="create_branch.php?delete=<?= $row['id'] ?>" onclick="return confirm('Delete?')" class="w-7 h-7 bg-red-100 text-red-600 rounded flex items-center justify-center inline-block"><i class="fa fa-trash"></i></a>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</main>

</body>
</html>
