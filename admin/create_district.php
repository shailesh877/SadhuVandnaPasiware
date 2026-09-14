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

// Create tables if they do not exist
mysqli_query($con, "CREATE TABLE IF NOT EXISTS tbl_states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($con, "CREATE TABLE IF NOT EXISTS tbl_districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state_id INT NOT NULL,
    district_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (state_id) REFERENCES tbl_states(id) ON DELETE CASCADE
)");

date_default_timezone_set("Asia/Kolkata");
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_district'])) {
        $state_id = intval($_POST['state_id'] ?? 0);
        $district_name = trim($_POST['district_name'] ?? '');

        if($state_id <= 0) $errors[] = "Please select a valid state.";
        if($district_name == '') $errors[] = "District name is required.";

        if(empty($errors)){
            $district_name = mysqli_real_escape_string($con, $district_name);
            $created_at = date("Y-m-d H:i:s");

            $sql = "INSERT INTO tbl_districts (state_id, district_name, created_at) VALUES ($state_id, '$district_name', '$created_at')";
            if(mysqli_query($con, $sql)) {
                header("Location: create_district.php?success=1");
                exit;
            } else {
                $errors[] = "Database error: " . mysqli_error($con);
            }
        }
    } elseif (isset($_POST['update_district'])) {
        $id = intval($_POST['district_id']);
        $state_id = intval($_POST['state_id'] ?? 0);
        $district_name = trim($_POST['district_name'] ?? '');

        if($state_id <= 0) $errors[] = "Please select a valid state.";
        if($district_name == '') $errors[] = "District name is required.";

        if(empty($errors)){
            $district_name = mysqli_real_escape_string($con, $district_name);
            
            $sql = "UPDATE tbl_districts SET state_id=$state_id, district_name='$district_name' WHERE id=$id";
            if(mysqli_query($con, $sql)) {
                header("Location: create_district.php?success=2");
                exit;
            } else {
                $errors[] = "Database error: " . mysqli_error($con);
            }
        }
    }
}

if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    mysqli_query($con, "DELETE FROM tbl_districts WHERE id=$id");
    header("Location: create_district.php");
    exit;
}

$edit_data = null;
if(isset($_GET['edit'])){
    $id = intval($_GET['edit']);
    $q = mysqli_query($con, "SELECT * FROM tbl_districts WHERE id=$id");
    $edit_data = mysqli_fetch_assoc($q);
}

// Fetch states for dropdown
$states = mysqli_query($con, "SELECT * FROM tbl_states ORDER BY state_name ASC");

// Fetch districts for list view
$districts = mysqli_query($con, "SELECT d.*, s.state_name FROM tbl_districts d LEFT JOIN tbl_states s ON d.state_id = s.id ORDER BY d.id DESC");
?>

<?php include("header.php"); ?>

<main class="flex gap-5 max-w-6xl mx-auto p-4 mt-4">

  <!-- LEFT FORM -->
  <div class="w-full md:w-2/5">
    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
      <h2 class="text-lg font-bold text-orange-600 mb-4"><i class="fa-solid fa-map-pin text-orange-500"></i> <?= $edit_data ? 'Edit District' : 'Create District' ?></h2>

      <?php if(isset($_GET['success'])): ?>
        <p class="text-green-600 font-semibold mb-2 bg-green-50 p-2 rounded">District <?= $_GET['success'] == 2 ? 'updated' : 'created' ?> successfully.</p>
      <?php endif; ?>
      <?php if(!empty($errors)): ?>
        <div class="text-red-600 mb-3 bg-red-50 p-2 rounded"><?php foreach($errors as $e) echo "• $e<br>"; ?></div>
      <?php endif; ?>

      <form method="POST" class="flex flex-col gap-4">
        <?php if($edit_data): ?>
          <input type="hidden" name="district_id" value="<?= $edit_data['id'] ?>">
        <?php endif; ?>
        <div>
          <label class="text-sm font-medium">Select State *</label>
          <select name="state_id" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-400 outline-none transition">
            <option value="">-- Choose State --</option>
            <?php mysqli_data_seek($states, 0); while($st = mysqli_fetch_assoc($states)): ?>
              <option value="<?= $st['id'] ?>" <?= ($edit_data && $edit_data['state_id'] == $st['id']) ? 'selected' : '' ?>><?= $st['state_name'] ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <div>
          <label class="text-sm font-medium">District Name *</label>
          <input type="text" name="district_name" required placeholder="e.g. Mumbai" value="<?= $edit_data ? $edit_data['district_name'] : '' ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-orange-400 outline-none transition">
        </div>

        <div class="flex gap-3 mt-2">
          <?php if($edit_data): ?>
            <button name="update_district" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 shadow transition w-full">Update</button>
            <a href="create_district.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 text-center shadow transition w-full">Cancel</a>
          <?php else: ?>
            <button name="submit_district" class="bg-orange-500 text-white px-6 py-2 rounded-lg hover:bg-orange-600 shadow transition w-full">Submit</button>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div> <!-- END OF LEFT FORM -->

  <!-- LIST VIEW (Mobile + Desktop) -->
  <div class="w-full md:w-3/5">
    
    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden mb-4">
      <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50">
        <div class="flex items-center gap-3">
          <a href="index" class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
            <i class="fa-solid fa-arrow-left text-orange-600 text-sm"></i>
          </a>
          <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
            District List
          </h2>
        </div>
        
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <div class="relative w-full sm:w-64">
            <i class="fa-solid fa-search absolute left-3 top-2.5 text-orange-400 text-sm"></i>
            <input 
              type="search" 
              id="searchInput" 
              placeholder="Search district..." 
              class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 shadow-sm rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition text-sm text-gray-700"
            >
          </div>
          <div class="text-sm font-medium text-gray-500 bg-white px-3 py-1.5 rounded-lg border shadow-sm whitespace-nowrap">
            Total: <span class="text-orange-600 font-bold"><?= mysqli_num_rows($districts) ?></span>
          </div>
        </div>
      </div>

      <!-- Desktop Table -->
      <div class="hidden md:block overflow-y-auto max-h-[525px]">
        <table class="w-full text-sm border-t border-gray-100">
          <thead class="bg-orange-50 text-gray-700 sticky top-0 border-b">
            <tr>
              <th class="px-4 py-3 text-left">Sr No.</th>
              <th class="px-4 py-3 text-left">District Name</th>
              <th class="px-4 py-3 text-left">State</th>
              <th class="px-4 py-3 text-center">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
          <?php if(mysqli_num_rows($districts) > 0): ?>
            <?php mysqli_data_seek($districts,0); $sr = 1; while($row=mysqli_fetch_assoc($districts)): ?>
              <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 text-gray-500 font-mono text-xs"><?= $sr++ ?></td>
                <td class="px-4 py-3 font-semibold text-gray-800"><?= $row['district_name'] ?></td>
                <td class="px-4 py-3 text-gray-600"><?= $row['state_name'] ?></td>
                <td class="px-4 py-3 text-center flex justify-center gap-2">
                  <a href="create_district.php?edit=<?= $row['id'] ?>" class="w-8 h-8 bg-blue-50 text-blue-500 hover:bg-blue-500 hover:text-white rounded flex items-center justify-center transition shadow-sm" title="Edit">
                    <i class="fa fa-edit"></i>
                  </a>
                  <a href="create_district.php?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this district?')" class="w-8 h-8 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded flex items-center justify-center transition shadow-sm" title="Delete">
                    <i class="fa fa-trash"></i>
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
             <tr><td colspan="4" class="text-center py-6 text-gray-400">No districts found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div> <!-- /bg-white -->

    <!-- Mobile Cards -->
    <div class="md:hidden space-y-4" id="mobileCards">
      <?php if(mysqli_num_rows($districts) > 0): ?>
        <?php mysqli_data_seek($districts,0); $sr = 1; while($row=mysqli_fetch_assoc($districts)): ?>
        <div class="bg-white border rounded-xl p-4 shadow-sm flex flex-col gap-2 mobile-card" 
             data-search="<?= htmlspecialchars(strtolower($row['district_name'].' '.$row['state_name'])) ?>">
          <div class="flex justify-between items-start">
            <div>
               <div class="font-bold text-gray-800 text-lg"><?= $row['district_name'] ?></div>
               <div class="text-sm text-gray-600 mt-1"><i class="fa-solid fa-map text-xs text-gray-400"></i> <?= $row['state_name'] ?></div>
               <div class="text-xs text-gray-400 mt-1">Sr No: <?= $sr++ ?></div>
            </div>
            <div class="flex gap-2">
              <a href="create_district.php?edit=<?= $row['id'] ?>" class="w-8 h-8 bg-blue-50 text-blue-500 flex items-center justify-center rounded-lg shadow-sm"><i class="fa fa-edit"></i></a>
              <a href="create_district.php?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this district?')" class="w-8 h-8 bg-red-50 text-red-500 flex items-center justify-center rounded-lg shadow-sm"><i class="fa fa-trash"></i></a>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="text-center py-6 text-gray-400">No districts found.</div>
      <?php endif; ?>
    </div>
  </div>

</main>

<script>
// Live Search
document.getElementById("searchInput").addEventListener("input", function(){
  let v = this.value.toLowerCase();
  
  // Desktop table search
  document.querySelectorAll("tbody tr").forEach(r => {
    if(r.querySelector("td[colspan]")) return; // skip empty message row
    r.style.display = r.innerText.toLowerCase().includes(v) ? "" : "none";
  });
  
  // Mobile cards search
  document.querySelectorAll(".mobile-card").forEach(c => {
    c.style.display = c.dataset.search.includes(v) ? "" : "none";
  });
});
</script>

</body>
</html>
