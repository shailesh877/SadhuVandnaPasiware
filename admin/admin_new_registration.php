<?php
session_start();
include("../connection.php");

// Handle Approve / Block
if(isset($_POST['action']) && isset($_POST['id'])){
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    if($action === "approve"){
        $status = "Approved";
    } elseif($action === "block"){
        $status = "Blocked";
    } else {
        $_SESSION['msg'] = "Invalid action!";
        header("Location: admin_new_registration.php");
        exit;
    }

    $q = mysqli_query($con, "UPDATE tbl_members SET status='$status' WHERE id=$id");
    if($q){
        $_SESSION['msg'] = "Member $status successfully!";
    } else {
        $_SESSION['msg'] = "Database error!";
    }
    header("Location: admin_new_registration.php");
    exit;
}

// Fetch all pending users
$users = mysqli_query($con, "SELECT * FROM tbl_members WHERE status='Pending' ORDER BY date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Sadhu Vandana - New Registrations</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen">

<!-- Header -->
<header class="bg-white shadow-md sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
    <div class="flex items-center gap-3">
      <a href="index" class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition">
        <i class="fa-solid fa-arrow-left text-orange-600"></i>
      </a>
      <div>
        <h1 class="text-xl md:text-2xl font-bold text-gray-800">New Registrations</h1>
        <p class="text-xs text-gray-500">Review and approve pending members</p>
      </div>
    </div>
    <div class="flex gap-2 items-center">
      <span class="px-3 py-1.5 bg-orange-100 text-orange-600 rounded-full text-sm font-semibold" id="pending-count"><?= mysqli_num_rows($users) ?> Pending</span>
      <input type="text" id="searchInput" placeholder="Search by name, email, phone, cast" class="px-3 py-2 border rounded-lg w-full md:w-64 text-sm">
    </div>
  </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

<?php if(isset($_SESSION['msg'])): ?>
<div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
<?php endif; ?>

<!-- Desktop Table -->
<div class="hidden md:block bg-white rounded-xl shadow-lg overflow-hidden mt-4">
  <div class="max-h-[calc(100vh-200px)] overflow-y-auto">
    <table class="w-full text-sm" id="userTable">
      <thead class="bg-gradient-to-r from-orange-500 to-orange-600 text-white sticky top-0 z-10">
        <tr>
          <th class="px-3 py-2 text-left">Photo</th>
          <th class="px-3 py-2 text-left">Name</th>
          <th class="px-3 py-2 text-left">Email</th>
          <th class="px-3 py-2 text-left">Phone</th>
          <th class="px-3 py-2 text-left">Cast</th>
          <th class="px-3 py-2 text-left">Date</th>
          <th class="px-3 py-2 text-left">Status</th>
          <th class="px-3 py-2 text-center">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <?php while($user = mysqli_fetch_assoc($users)): ?>
        <tr class="hover:bg-orange-50 transition">
          <td class="px-3 py-2">
            <?php if($user['profile_photo'] && file_exists("../uploads/photo/".$user['profile_photo'])): ?>
              <img src="../uploads/photo/<?= $user['profile_photo'] ?>" class="w-12 h-12 rounded-full object-cover cursor-pointer view-photo"/>
            <?php else: ?>
              <i class="fa-solid fa-user text-gray-300 text-xl"></i>
            <?php endif; ?>
          </td>
          <td class="px-3 py-2 font-medium"><?= htmlspecialchars($user['name']) ?></td>
          <td class="px-3 py-2"><?= htmlspecialchars($user['email']) ?></td>
          <td class="px-3 py-2"><?= htmlspecialchars($user['mobile']) ?></td>
          <td class="px-3 py-2"><?= htmlspecialchars($user['cast']) ?></td>
          <td class="px-3 py-2"><?= date("d M Y", strtotime($user['date'])) ?></td>
          <td class="px-3 py-2">
            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-semibold">Pending</span>
          </td>
          <td class="px-3 py-2 text-center">
            <form method="post" class="flex justify-center gap-2">
              <input type="hidden" name="id" value="<?= $user['id'] ?>">
              <button type="submit" name="action" value="approve" class="w-8 h-8 bg-green-100 hover:bg-green-200 text-green-600 rounded-lg transition" title="Approve">
                <i class="fa-solid fa-check text-sm"></i>
              </button>
              <button type="submit" name="action" value="block" class="w-8 h-8 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg transition" title="Block">
                <i class="fa-solid fa-ban text-sm"></i>
              </button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Mobile Cards -->
<div class="md:hidden space-y-4 mt-4" id="userCards">
<?php
mysqli_data_seek($users, 0);
while($user = mysqli_fetch_assoc($users)):
?>
<div class="bg-white rounded-xl shadow-lg p-4 user-card">
  <div class="flex items-start justify-between mb-3">
    <div class="flex items-center gap-3">
      <?php if($user['profile_photo'] && file_exists("../uploads/photo/".$user['profile_photo'])): ?>
        <img src="../uploads/photo/<?= $user['profile_photo'] ?>" class="w-12 h-12 rounded-full object-cover cursor-pointer view-photo"/>
      <?php else: ?>
        <i class="fa-solid fa-user text-gray-300 w-12 h-12 text-2xl flex items-center justify-center rounded-full bg-gray-100"></i>
      <?php endif; ?>
      <div>
        <h3 class="text-base font-bold text-gray-800"><?= htmlspecialchars($user['name']) ?></h3>
        <p class="text-xs text-gray-500"><?= htmlspecialchars($user['cast']) ?> | <?= date("d M Y", strtotime($user['date'])) ?></p>
      </div>
    </div>
    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-semibold">Pending</span>
  </div>
  <div class="space-y-2 mb-3">
    <div class="flex items-center gap-2 text-sm text-gray-600">
      <i class="fa-solid fa-envelope text-orange-500 w-4"></i>
      <span><?= htmlspecialchars($user['email']) ?></span>
    </div>
    <div class="flex items-center gap-2 text-sm text-gray-600">
      <i class="fa-solid fa-phone text-orange-500 w-4"></i>
      <span><?= htmlspecialchars($user['mobile']) ?></span>
    </div>
  </div>
  <div class="grid grid-cols-2 gap-2">
    <form method="post">
      <input type="hidden" name="id" value="<?= $user['id'] ?>">
      <button type="submit" name="action" value="approve" class="flex flex-col items-center gap-1 px-3 py-2 bg-green-100 hover:bg-green-200 text-green-600 rounded-lg transition w-full">
        <i class="fa-solid fa-check text-lg"></i>
        <span class="text-xs font-medium">Approve</span>
      </button>
    </form>
    <form method="post">
      <input type="hidden" name="id" value="<?= $user['id'] ?>">
      <button type="submit" name="action" value="block" class="flex flex-col items-center gap-1 px-3 py-2 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg transition w-full">
        <i class="fa-solid fa-ban text-lg"></i>
        <span class="text-xs font-medium">Block</span>
      </button>
    </form>
  </div>
</div>
<?php endwhile; ?>
</div>

<!-- Image Modal -->
<div id="photoModal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center z-50">
    <span id="closePhotoModal" class="absolute top-5 right-5 text-white text-3xl cursor-pointer">&times;</span>
    <img id="modalUserPhoto" class="max-h-full max-w-full rounded-lg shadow-lg" />
</div>

<script>
// Modal Image view
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
document.getElementById("photoModal").onclick = e => {
    if(e.target === document.getElementById("photoModal")){
        document.getElementById("photoModal").classList.add("hidden");
        document.getElementById("photoModal").classList.remove("flex");
    }
};

// Live Search
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('input', () => {
    const val = searchInput.value.toLowerCase();
    // Desktop table
    document.querySelectorAll('#userTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
    });
    // Mobile cards
    document.querySelectorAll('#userCards .user-card').forEach(card => {
        card.style.display = card.innerText.toLowerCase().includes(val) ? '' : 'none';
    });
    // Update pending count
    const count = document.querySelectorAll('#userTable tbody tr:not([style*="display: none"])').length;
    document.getElementById('pending-count').innerText = count + ' Pending';
});
</script>

</body>
</html>
