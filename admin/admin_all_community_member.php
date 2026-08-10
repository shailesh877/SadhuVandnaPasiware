<?php
session_start();
include("../connection.php");

// Handle Block (approved members ko block karne ke liye)
if(isset($_POST['action']) && isset($_POST['id'])){
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    if($action === "block"){
        $status = "Blocked";
        $q = mysqli_query($con, "UPDATE tbl_members SET status='$status' WHERE id=$id");
        if($q){
            $_SESSION['msg'] = "Member blocked successfully!";
        } else {
            $_SESSION['msg'] = "Database error!";
        }
    }
    header("Location: admin_all_community_member.php");
    exit;
}

// Fetch only approved members
$members = mysqli_query($con, "SELECT * FROM tbl_members WHERE status='Approved' ORDER BY date DESC");
?>


<?php include("header.php"); ?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">

<?php if(isset($_SESSION['msg'])): ?>
<div class="mb-2 p-1 bg-green-100 text-green-800 rounded text-sm text-center font-semibold border border-green-300">
  <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
</div>
<?php endif; ?>

<div class="hidden md:block bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
  
  <div class="p-2 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50">
    <div class="flex items-center gap-3">
      <a href="index.php" class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
        <i class="fa-solid fa-arrow-left text-orange-600 text-sm"></i>
      </a>
      <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
        Community Members
      </h2>
    </div>
    
    <div class="flex items-center gap-2 w-full sm:w-auto">
      <div class="relative w-full sm:w-64">
        <i class="fa-solid fa-search absolute left-3 top-2.5 text-orange-400 text-sm"></i>
        <input 
          type="text" 
          id="searchInput" 
          placeholder="Search..." 
          class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 shadow-sm rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition text-sm text-gray-700"
        >
      </div>
      <div class="text-sm font-medium text-gray-500 bg-white px-3 py-1.5 rounded-lg border shadow-sm whitespace-nowrap">
        Total: <span class="text-orange-600 font-bold"><?= mysqli_num_rows($members) ?></span>
      </div>
    </div>
  </div>

  <div class="overflow-y-auto" style="max-height: calc(100vh - 130px);">
    <table class="w-full text-sm" id="memberTable">
      <thead class="bg-gradient-to-r from-orange-500 to-orange-600 text-white sticky top-0 z-10">
        <tr>
          <th class="px-6 py-4 text-left">#</th>
          <th class="px-6 py-4 text-left">Photo</th>
          <th class="px-6 py-4 text-left">Name</th>
          <th class="px-6 py-4 text-left">Email</th>
          <th class="px-6 py-4 text-left">Phone</th>
          <th class="px-6 py-4 text-left">Community/Caste</th>
          <th class="px-6 py-4 text-left">Joined</th>
          <th class="px-6 py-4 text-left">Status</th>
          <th class="px-6 py-4 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $a=1; while($m = mysqli_fetch_assoc($members)): ?>
        <tr class="hover:bg-orange-50 transition">
          <td class="px-6 py-4"><?= $a;?></td>
          <td class="px-6 py-4">
            <img src="../uploads/photo/<?= $m['profile_photo'] ?>" class="w-10 h-10 rounded-full cursor-pointer view-photo" alt="photo">
          </td>
          <td class="px-6 py-4"><?= htmlspecialchars($m['name']) ?></td>
          <td class="px-6 py-4"><?= htmlspecialchars($m['email']) ?></td>
          <td class="px-6 py-4"><?= htmlspecialchars($m['mobile']) ?></td>
          <td class="px-6 py-4"><?= htmlspecialchars($m['cast']) ?></td>
          <td class="px-6 py-4"><?= date("d M Y", strtotime($m['date'])) ?></td>
          <td class="px-6 py-4">
            <?php if($m['status']=="Pending"): ?>
              <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-semibold">Pending</span>
            <?php elseif($m['status']=="Approved"): ?>
              <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Approved</span>
            <?php else: ?>
              <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Blocked</span>
            <?php endif; ?>
          </td>
          <td class="px-6 py-4 text-center">
            <?php if($m['status']=="Pending"): ?>
            <form method="post" class="flex justify-center gap-2">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button type="submit" name="action" value="approve" class="w-8 h-8 bg-green-100 hover:bg-green-200 text-green-600 rounded-lg transition" title="Approve">
                <i class="fa-solid fa-check text-sm"></i>
              </button>
              <button type="submit" name="action" value="block" class="w-8 h-8 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg transition" title="Block">
                <i class="fa-solid fa-ban text-sm"></i>
              </button>
            </form>
            <?php elseif($m['status']=="Approved"): ?>
              <form method="post">
                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                <button type="submit" name="action" value="block" class="w-8 h-8 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg transition" title="Block">
                  <i class="fa-solid fa-ban text-sm"></i>
                </button>
              </form>
            <?php endif; ?>
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
// mysqli_data_seek($members,0);
// mysqli_data_seek($members,0);
$members = mysqli_query($con, "SELECT * FROM tbl_members WHERE status='Approved' ORDER BY date DESC");
while($m = mysqli_fetch_assoc($members)):
?>
<div class="bg-white rounded-xl shadow-lg p-4 user-card">
  <div class="flex items-start justify-between mb-3">
    <div class="flex items-center gap-3">
      <img src="../uploads/photo/<?= $m['profile_photo'] ?>" class="w-12 h-12 rounded-full cursor-pointer view-photo" alt="photo">
      <div>
        <h3 class="text-base font-bold text-gray-800"><?= htmlspecialchars($m['name']) ?></h3>
        <p class="text-xs text-gray-500">#<?= $m['id'] ?></p>
        <p class="text-xs text-gray-500"><?= htmlspecialchars($m['cast']) ?></p>
      </div>
    </div>
    <span class="px-2 py-1 <?php if($m['status']=="Pending") echo 'bg-yellow-100 text-yellow-700'; elseif($m['status']=="Approved") echo 'bg-green-100 text-green-700'; else echo 'bg-red-100 text-red-700'; ?> rounded-full text-xs font-semibold"><?= $m['status'] ?></span>
  </div>
  <div class="space-y-2 mb-4 text-sm text-gray-600">
    <div class="flex items-center gap-2"><i class="fa-solid fa-envelope text-orange-500 w-4"></i><span><?= htmlspecialchars($m['email']) ?></span></div>
    <div class="flex items-center gap-2"><i class="fa-solid fa-phone text-orange-500 w-4"></i><span><?= htmlspecialchars($m['mobile']) ?></span></div>
    <div class="flex items-center gap-2"><i class="fa-solid fa-calendar text-orange-500 w-4"></i><span><?= date("d M Y", strtotime($m['date'])) ?></span></div>
  </div>
  <div class="flex gap-2">
    <?php if($m['status']=="Pending"): ?>
    <form method="post" class="flex gap-2 w-full">
      <input type="hidden" name="id" value="<?= $m['id'] ?>">
      <button type="submit" name="action" value="approve" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-green-100 hover:bg-green-200 text-green-600 rounded-lg transition">
        <i class="fa-solid fa-check text-lg"></i>
        <span class="text-sm font-medium">Approve</span>
      </button>
      <button type="submit" name="action" value="block" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg transition">
        <i class="fa-solid fa-ban text-lg"></i>
        <span class="text-sm font-medium">Block</span>
      </button>
    </form>
    <?php elseif($m['status']=="Approved"): ?>
      <form method="post" class="w-full">
        <input type="hidden" name="id" value="<?= $m['id'] ?>">
        <button type="submit" name="action" value="block" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg transition">
          <i class="fa-solid fa-ban text-lg"></i>
          <span class="text-sm font-medium">Block</span>
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php endwhile; ?>
</div>

<!-- Photo Modal -->
<div id="photoModal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center z-50">
  <span id="closePhotoModal" class="absolute top-5 right-5 text-white text-3xl cursor-pointer">&times;</span>
  <img id="modalUserPhoto" class="max-h-full max-w-full rounded-lg shadow-lg"/>
</div>

<script>
// Image modal
document.querySelectorAll(".view-photo").forEach(img=>{
  img.onclick=()=> {
    document.getElementById("modalUserPhoto").src=img.src;
    document.getElementById("photoModal").classList.remove("hidden");
    document.getElementById("photoModal").classList.add("flex");
  };
});
document.getElementById("closePhotoModal").onclick=()=> {
  document.getElementById("photoModal").classList.add("hidden");
  document.getElementById("photoModal").classList.remove("flex");
};
document.getElementById("photoModal").onclick=e=>{
  if(e.target===document.getElementById("photoModal")){
    document.getElementById("photoModal").classList.add("hidden");
    document.getElementById("photoModal").classList.remove("flex");
  }
};

// Live search
const searchInput=document.getElementById('searchInput');
searchInput.addEventListener('input',()=>{
  const val=searchInput.value.toLowerCase();
  document.querySelectorAll('#memberTable tbody tr, #mobileCards .user-card').forEach(el=>{
    el.style.display = el.innerText.toLowerCase().includes(val) ? '' : 'none';
  });
});
</script>

</body>
</html>
