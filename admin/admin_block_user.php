<?php
session_start();
include("../connection.php");

// Handle Unblock
if(isset($_POST['action']) && isset($_POST['id'])){
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    if($action === "unblock"){
        $status = "Approved";
        $q = mysqli_query($con, "UPDATE tbl_members SET status='$status' WHERE id=$id");
        if($q){
            $_SESSION['msg'] = "Member unblocked successfully!";
        } else {
            $_SESSION['msg'] = "Database error!";
        }
    }
    header("Location: admin_block_user.php");
    exit;
}

// Fetch only blocked members
$members = mysqli_query($con, "SELECT * FROM tbl_members WHERE status='Blocked' ORDER BY date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Sadhu Vandana - Blocked Members</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
  /* Simple scrollable table max height */
  .table-container { max-height: calc(100vh - 200px); overflow-y: auto; }
</style>
</head>
<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen">

<!-- Header -->
<header class="bg-white shadow-md sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <a href="index" class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition">
          <i class="fa-solid fa-arrow-left text-orange-600"></i>
        </a>
        <div>
          <h1 class="text-xl md:text-2xl font-bold text-gray-800">Blocked Members</h1>
          <p class="text-xs text-gray-500">Manage all blocked members</p>
        </div>
      </div>
      <span class="px-3 py-1.5 bg-red-100 text-red-600 rounded-full text-sm font-semibold"><?= mysqli_num_rows($members) ?> Blocked</span>
    </div>
  </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

<?php if(isset($_SESSION['msg'])): ?>
  <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
      <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
  </div>
<?php endif; ?>

<!-- Desktop Table -->
<div class="hidden md:block bg-white rounded-xl shadow-lg overflow-hidden">
  <div class="table-container">
    <table class="w-full text-sm">
      <thead class="bg-gradient-to-r from-orange-500 to-orange-600 text-white sticky top-0 z-10">
        <tr>
          <th class="px-6 py-4 text-left">#</th>
          <th class="px-6 py-4 text-left">Name</th>
          <th class="px-6 py-4 text-left">Email</th>
          <th class="px-6 py-4 text-left">Phone</th>
          <th class="px-6 py-4 text-left">Community</th>
          <th class="px-6 py-4 text-left">Date</th>
          <th class="px-6 py-4 text-left">Status</th>
          <th class="px-6 py-4 text-center">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200" id="tableBody">
      <?php $a=1; while($member = mysqli_fetch_assoc($members)): ?>
        <tr class="hover:bg-orange-50 transition">
          <td class="px-6 py-4"><?= $a; ?></td>
          <td class="px-6 py-4 flex items-center gap-3">
            <?php if($member['profile_photo'] && file_exists("../uploads/photo/".$member['profile_photo'])): ?>
              <img src="../uploads/photo/<?= $member['profile_photo'] ?>" class="w-10 h-10 rounded-full object-cover cursor-pointer view-photo" />
            <?php else: ?>
              <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold text-sm"><?= strtoupper(substr($member['name'],0,2)) ?></div>
            <?php endif; ?>
            <?= htmlspecialchars($member['name']) ?>
          </td>
          <td class="px-6 py-4"><?= htmlspecialchars($member['email']) ?></td>
          <td class="px-6 py-4"><?= htmlspecialchars($member['mobile']) ?></td>
          <td class="px-6 py-4"><?= htmlspecialchars($member['cast']) ?></td>
          <td class="px-6 py-4"><?= date("d M Y", strtotime($member['date'])) ?></td>
          <td class="px-6 py-4">
            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Blocked</span>
          </td>
          <td class="px-6 py-4 text-center">
            <form method="post">
              <input type="hidden" name="id" value="<?= $member['id'] ?>">
              <button type="submit" name="action" value="unblock" class="w-8 h-8 bg-green-100 hover:bg-green-200 text-green-600 rounded-lg transition" title="Unblock">
                  <i class="fa-solid fa-check text-sm"></i>
              </button>
            </form>
          </td>
        </tr>
      <?php $a++; endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Mobile Card View -->
<div class="md:hidden space-y-4" id="mobileCards">
<?php
$a=1;
mysqli_data_seek($members, 0);
while($member = mysqli_fetch_assoc($members)):
?>
<div class="bg-white rounded-xl shadow-lg p-4">
  <div class="flex items-start justify-between mb-3">
    <div class="flex items-center gap-3">
      <?php if($member['profile_photo'] && file_exists("../uploads/photo/".$member['profile_photo'])): ?>
        <img src="../uploads/photo/<?= $member['profile_photo'] ?>" class="w-12 h-12 rounded-full object-cover cursor-pointer view-photo"/>
      <?php else: ?>
        <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold text-sm"><?= strtoupper(substr($member['name'],0,2)) ?></div>
      <?php endif; ?>
      <div>
        <h3 class="text-base font-bold text-gray-800"><?= htmlspecialchars($member['name']) ?></h3>
        <p class="text-xs text-gray-500">#<?= $a; ?></p>
      </div>
    </div>
    <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Blocked</span>
  </div>
  <div class="space-y-2 mb-4">
    <div class="flex items-center gap-2 text-sm text-gray-600">
      <i class="fa-solid fa-envelope text-orange-500 w-4"></i>
      <span><?= htmlspecialchars($member['email']) ?></span>
    </div>
    <div class="flex items-center gap-2 text-sm text-gray-600">
      <i class="fa-solid fa-phone text-orange-500 w-4"></i>
      <span><?= htmlspecialchars($member['mobile']) ?></span>
    </div>
    <div class="flex items-center gap-2 text-sm text-gray-600">
      <i class="fa-solid fa-branch-code text-orange-500 w-4"></i>
      <span><?= htmlspecialchars($member['cast']) ?></span>
    </div>
    <div class="flex items-center gap-2 text-sm text-gray-600">
      <i class="fa-solid fa-calendar text-orange-500 w-4"></i>
      <span><?= date("d M Y", strtotime($member['date'])) ?></span>
    </div>
  </div>
  <form method="post">
    <input type="hidden" name="id" value="<?= $member['id'] ?>">
    <button type="submit" name="action" value="unblock" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-green-100 hover:bg-green-200 text-green-600 rounded-lg transition">
      <i class="fa-solid fa-check text-lg"></i>
      <span class="text-sm font-medium">Unblock Member</span>
    </button>
  </form>
</div>
<?php $a++; endwhile; ?>
</div>

<!-- Image Modal -->
<div id="photoModal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center z-50">
    <span id="closePhotoModal" class="absolute top-5 right-5 text-white text-3xl cursor-pointer">&times;</span>
    <img id="modalUserPhoto" class="max-h-full max-w-full rounded-lg shadow-lg" />
</div>

<script>
// Image modal
document.querySelectorAll(".view-photo").forEach(img => {
    img.onclick = () => {
        document.getElementById("modalUserPhoto").src = img.src;
        document.getElementById("photoModal").classList.remove("hidden");
        document.getElementById("photoModal").classList.add("flex");
    };
});
document.getElementById("closePhotoModal").onclick = () => {
    document.getElementById("photoModal").classList.add("hidden");
    document.getElementById("photoModal").classList.remove("flex");
};
document.getElementById("photoModal").onclick = (e) => {
    if(e.target === document.getElementById("photoModal")){
        document.getElementById("photoModal").classList.add("hidden");
        document.getElementById("photoModal").classList.remove("flex");
    }
};

// Optional: add live search if needed
</script>

</body>
</html>
