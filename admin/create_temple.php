<?php
session_start();
include("../connection.php");

if (!isset($_SESSION['admin_id'])) {

    // Check If Cookie Exists
    if (isset($_COOKIE['sadhu_admin_id']) && isset($_COOKIE['sadhu_admin_token'])) {

        $id = $_COOKIE['sadhu_admin_id'];
        $token = $_COOKIE['sadhu_admin_token'];

        $q = mysqli_query($con, "SELECT * FROM tbl_admin WHERE admin_id='$id' LIMIT 1");

        if (mysqli_num_rows($q) == 1) {
            $row = mysqli_fetch_assoc($q);

            // Verify Cookie Token
            if (sha1($row['password']) === $token) {

                // Auto-Login using Cookie
                $_SESSION['admin_id'] = $row['admin_id'];
                $_SESSION['admin_name'] = $row['username'];

            }
        }
    }
}

// Still not logged in → redirect to login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login");
    exit;
}

date_default_timezone_set("Asia/Kolkata");

// ---------------- Delete Temple ----------------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $res = mysqli_query($con, "SELECT photo FROM tbl_temple WHERE temple_id=$id");
    $old = mysqli_fetch_assoc($res);

    if ($old['photo'] != "" && file_exists("../uploads/temple/" . $old['photo'])) {
        unlink("../uploads/temple/" . $old['photo']);
    }

    mysqli_query($con, "DELETE FROM tbl_temple WHERE temple_id=$id");

    echo "<script>alert('Temple Deleted'); window.location='create_temple.php';</script>";
}

// ---------------- Add Temple ----------------
if (isset($_POST['addTemple'])) {

    $mahant_name = $_POST['mahant_name'];
    $mobile = $_POST['mobile'];
    $village = $_POST['village'];
    $taluka = $_POST['taluka'];
    $district = $_POST['district'];

    $photo = "";
    if (!empty($_FILES['photo']['name'])) {

        $imgName = time() . "_" . $_FILES['photo']['name'];
        $target = "../uploads/temple/" . $imgName;

        if (!is_dir("../uploads/temple")) {
            mkdir("../uploads/temple", 0777, true);
        }

        move_uploaded_file($_FILES['photo']['tmp_name'], $target);
        $photo = $imgName;
    }

    $created_at = date("Y-m-d H:i:s");

    mysqli_query($con, "INSERT INTO tbl_temple 
        (mahant_name, mobile, village, taluka, district, photo, created_at)
        VALUES ('$mahant_name','$mobile','$village','$taluka','$district','$photo','$created_at')");

    echo "<script>alert('Temple Added'); window.location='create_temple.php';</script>";
}

$temples = mysqli_query($con, "SELECT * FROM tbl_temple ORDER BY temple_id DESC");
?>

<?php include("header.php"); ?>



<main class="flex-1 flex flex-col md:flex-row gap-8 p-4 w-full justify-center">

    <!-- Add Form -->
    <div class="w-full md:max-w-md">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-bold flex items-center gap-2 text-gray-800">
                <i class="fa-solid fa-plus text-orange-500"></i>
                Add Temple
            </h2>

            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">

                <!-- Photo -->
                <div class="md:col-span-2 flex flex-col items-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full border-2 border-dashed border-orange-300 flex items-center justify-center overflow-hidden mb-2">
                        <img id="previewImg" src="" class="hidden w-full h-full object-cover">
                        <i id="defaultIcon" class="fa-solid fa-user-tie text-gray-300 text-3xl"></i>
                    </div>

                    <input type="file" name="photo" id="photo" class="hidden" accept="image/*" onchange="showPreview(event)">
                    <label for="photo" class="px-4 py-1 bg-orange-500 text-white rounded-full text-xs cursor-pointer">
                        Choose Photo
                    </label>
                </div>

                <div><label class="text-sm">Mahant Name *</label>
                    <input type="text" name="mahant_name" required class="w-full border px-3 py-2 rounded-lg">
                </div>

                <div><label class="text-sm">Mobile *</label>
                    <input type="text" name="mobile" required class="w-full border px-3 py-2 rounded-lg">
                </div>

                <div><label class="text-sm">Village *</label>
                    <input type="text" name="village" required class="w-full border px-3 py-2 rounded-lg">
                </div>

                <div><label class="text-sm">Taluka *</label>
                    <input type="text" name="taluka" required class="w-full border px-3 py-2 rounded-lg">
                </div>

                <div><label class="text-sm">District *</label>
                    <input type="text" name="district" required class="w-full border px-3 py-2 rounded-lg">
                </div>

                <div class="md:col-span-2 flex gap-4">
                    <button type="submit" name="addTemple" class="px-6 py-2 bg-orange-500 text-white rounded-lg">
                        <i class="fa-solid fa-check mr-1"></i>Add
                    </button>
                    <button type="reset" class="px-6 py-2 bg-gray-200 rounded-lg">Reset</button>
                </div>

            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="w-full md:w-3/5">
      <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        
        <div class="p-2 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50">
          <div class="flex items-center gap-3">
            <a href="index.php" class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
              <i class="fa-solid fa-arrow-left text-orange-600 text-sm"></i>
            </a>
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
              Temple List
            </h2>
          </div>
          
          <div class="flex items-center gap-2 w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
              <i class="fa-solid fa-search absolute left-3 top-2.5 text-orange-400 text-sm"></i>
              <input 
                type="search" 
                id="searchInput" 
                placeholder="Search temple..." 
                class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 shadow-sm rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition text-sm text-gray-700"
              >
            </div>
            <div class="text-sm font-medium text-gray-500 bg-white px-3 py-1.5 rounded-lg border shadow-sm whitespace-nowrap">
              Total: <span class="text-orange-600 font-bold"><?= mysqli_num_rows($temples) ?></span>
            </div>
          </div>
        </div>

        <div class="overflow-y-auto max-h-[525px]">
          <table class="w-full text-sm border-t border-gray-100">
                    <thead class="bg-gradient-to-r from-orange-500 to-orange-600 text-white">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs">Photo</th>
                            <th class="px-3 py-2 text-left text-xs">Mahant</th>
                            <th class="px-3 py-2 text-left text-xs">Mobile</th>
                            <th class="px-3 py-2 text-left text-xs">Village</th>
                            <th class="px-3 py-2 text-left text-xs">Taluka</th>
                            <th class="px-3 py-2 text-left text-xs">District</th>
                            <th class="px-3 py-2 text-left text-xs">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">

                    <?php while ($row = mysqli_fetch_assoc($temples)) { ?>
                        <tr>
                            <td class="px-3 py-2">
                                <?php if ($row['photo'] != "") { ?>
                                    <img src="../uploads/temple/<?= $row['photo'] ?>" class="w-10 h-10 rounded-full object-cover">
                                <?php } else { ?>
                                    <i class="fa-solid fa-user-tie text-gray-300 text-xl"></i>
                                <?php } ?>
                            </td>

                            <td class="px-3 py-2"><?= $row['mahant_name'] ?></td>
                            <td class="px-3 py-2"><?= $row['mobile'] ?></td>
                            <td class="px-3 py-2"><?= $row['village'] ?></td>
                            <td class="px-3 py-2"><?= $row['taluka'] ?></td>
                            <td class="px-3 py-2"><?= $row['district'] ?></td>

                            <td class="px-3 py-2 flex gap-3">
                                <a href="edit_temple.php?id=<?= $row['temple_id'] ?>" 
                                   class="text-blue-600 text-lg"><i class="fa-solid fa-pen"></i></a>

                                <a href="create_temple.php?delete=<?= $row['temple_id'] ?>" 
                                   onclick="return confirm('Delete this?')" 
                                   class="text-red-600 text-lg"><i class="fa-solid fa-trash"></i></a>
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
document.getElementById("searchInput").addEventListener("input", function(){
  let v = this.value.toLowerCase();
  document.querySelectorAll("tbody tr").forEach(r => {
    r.style.display = r.innerText.toLowerCase().includes(v) ? "" : "none";
  });
});

function showPreview(event) {
    document.getElementById("previewImg").src = URL.createObjectURL(event.target.files[0]);
    document.getElementById("previewImg").classList.remove("hidden");
    document.getElementById("defaultIcon").classList.add("hidden");
}
</script>

</body>
</html>
