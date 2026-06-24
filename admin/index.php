<?php
include("header.php");
include("../connection.php");

// Fetch counts dynamically
$newRegCount = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as cnt FROM tbl_members WHERE status='Pending'"))['cnt'];
$approvedCount = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as cnt FROM tbl_members WHERE status='Approved'"))['cnt'];
$blockedCount = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as cnt FROM tbl_members WHERE status='Blocked'"))['cnt'];
$totalEarnings = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(payment_ammount) as total FROM tbl_wallet WHERE status='success'"))['total'];
$totalEarnings = $totalEarnings ? $totalEarnings : 0;
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  
  <!-- Member Management Cards-->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- Card 1: New Registration -->
    <a href="admin_new_registration" class="block bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 p-4 group border-t-4 border-orange-500">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-gradient-to-br from-orange-100 to-orange-200 flex items-center justify-center group-hover:scale-110 transition-transform">
          <i class="fa-solid fa-user-plus text-orange-600 text-2xl"></i>
        </div>
        <span class="text-xs font-semibold text-orange-600 bg-orange-100 px-3 py-1.5 rounded-full">New</span>
      </div>
      <h3 class="text-3xl font-bold text-gray-800 mb-1"><?= $newRegCount ?></h3>
      <p class="text-sm text-gray-600 font-medium">New Registration</p>
      <p class="text-xs text-gray-400 mt-2">Pending approval</p>
    </a>

    <!-- Card 2: All Members -->
    <a href="admin_all_community_member" class="block bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 p-4 group border-t-4 border-green-500">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-gradient-to-br from-green-100 to-green-200 flex items-center justify-center group-hover:scale-110 transition-transform">
          <i class="fa-solid fa-users text-green-600 text-2xl"></i>
        </div>
        <span class="text-xs font-semibold text-green-600 bg-green-100 px-3 py-1.5 rounded-full">Active</span>
      </div>
      <h3 class="text-3xl font-bold text-gray-800 mb-1"><?= $approvedCount ?></h3>
      <p class="text-sm text-gray-600 font-medium">All Members</p>
      <p class="text-xs text-gray-400 mt-2">Total registered</p>
    </a>

    <!-- Card 3: Blocked Members -->
    <a href="admin_block_user" class="block bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 p-4 group border-t-4 border-red-500">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-gradient-to-br from-red-100 to-red-200 flex items-center justify-center group-hover:scale-110 transition-transform">
          <i class="fa-solid fa-user-slash text-red-600 text-2xl"></i>
        </div>
        <span class="text-xs font-semibold text-red-600 bg-red-100 px-3 py-1.5 rounded-full">Blocked</span>
      </div>
      <h3 class="text-3xl font-bold text-gray-800 mb-1"><?= $blockedCount ?></h3>
      <p class="text-sm text-gray-600 font-medium">Blocked Members</p>
      <p class="text-xs text-gray-400 mt-2">Restricted access</p>
    </a>

    <!-- Card 4: Wallet / Total Earnings -->
    <a href="admin_wallet" class="block bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 p-4 group border-t-4 border-blue-500">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center group-hover:scale-110 transition-transform">
          <i class="fa-solid fa-wallet text-blue-600 text-2xl"></i>
        </div>
        <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-3 py-1.5 rounded-full">₹</span>
      </div>
      <h3 class="text-3xl font-bold text-gray-800 mb-1">₹<?php echo number_format($totalEarnings); ?></h3>
      <p class="text-sm text-gray-600 font-medium">Total Earnings</p>
      <p class="text-xs text-gray-400 mt-2">From all donations</p>
    </a>

  </div>

  <!-- Management Actions -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    
    <a href="create_temple" class="block bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 p-4 group hover:scale-105">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-white/10 bg-opacity-20 backdrop-blur flex items-center justify-center">
          <i class="fa-solid fa-place-of-worship text-white text-2xl"></i>
        </div>
        <i class="fa-solid fa-arrow-right text-white text-lg opacity-0 group-hover:opacity-100 transition"></i>
      </div>
      <h3 class="text-xl font-bold text-white mb-2">Create Temple</h3>
      <p class="text-sm text-orange-100">Add new temple location</p>
    </a>

    <a href="create_branch" class="block bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 p-4 group hover:scale-105">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-white/10 bg-opacity-20 backdrop-blur flex items-center justify-center">
          <i class="fa-solid fa-code-branch text-white text-2xl"></i>
        </div>
        <i class="fa-solid fa-arrow-right text-white text-lg opacity-0 group-hover:opacity-100 transition"></i>
      </div>
      <h3 class="text-xl font-bold text-white mb-2">Create Branch</h3>
      <p class="text-sm text-orange-100">Add temple branch</p>
    </a>

    <a href="add_views_news" class="block bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 p-4 group hover:scale-105">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-white/10 bg-opacity-20 backdrop-blur flex items-center justify-center">
          <i class="fa-solid fa-newspaper text-white text-2xl"></i>
        </div>
        <i class="fa-solid fa-arrow-right text-white text-lg opacity-0 group-hover:opacity-100 transition"></i>
      </div>
      <h3 class="text-xl font-bold text-white mb-2">Create News</h3>
      <p class="text-sm text-orange-100">Post latest updates</p>
    </a>
</div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <a href="admin_jobs_education" class="block bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 p-4 group hover:scale-105">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-white/10 bg-opacity-20 backdrop-blur flex items-center justify-center">
          <i class="fa-solid fa-chalkboard-user text-white text-2xl"></i>
        </div>
        <i class="fa-solid fa-arrow-right text-white text-lg opacity-0 group-hover:opacity-100 transition"></i>
      </div>
      <h3 class="text-xl font-bold text-white mb-2">Job And Education</h3>
      <p class="text-sm text-orange-100">Post latest updates</p>
    </a>
    <a href="admin_gallery" class="block bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 p-4 group hover:scale-105">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-white/10 bg-opacity-20 backdrop-blur flex items-center justify-center">
          <i class="fa-solid fa-image text-white text-2xl"></i>
        </div>
        <i class="fa-solid fa-arrow-right text-white text-lg opacity-0 group-hover:opacity-100 transition"></i>
      </div>
      <h3 class="text-xl font-bold text-white mb-2">Add Gallery</h3>
      <p class="text-sm text-orange-100">Post latest updates</p>
    </a>
    <a href="admin_post" class="block bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 p-4 group hover:scale-105">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-white/10 bg-opacity-20 backdrop-blur flex items-center justify-center">
          <i class="fa-solid fa-photo-film text-white text-2xl"></i>
        </div>
        <i class="fa-solid fa-arrow-right text-white text-lg opacity-0 group-hover:opacity-100 transition"></i>
      </div>
      <h3 class="text-xl font-bold text-white mb-2">Manage Posts</h3>
      <p class="text-sm text-orange-100">All Members' Posts</p>
    </a>
<!-- Festival Frames Card -->
    <a href="admin_festival_frames.php" class="block bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 p-4 group hover:scale-105">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-white/10 bg-opacity-20 backdrop-blur flex items-center justify-center">
          <i class="fa-solid fa-wand-magic-sparkles text-white text-2xl"></i>
        </div>
        <i class="fa-solid fa-arrow-right text-white text-lg opacity-0 group-hover:opacity-100 transition"></i>
      </div>
      <h3 class="text-xl font-bold text-white mb-2">Festival Frames</h3>
      <p class="text-sm text-orange-100">Add/Remove Frames</p>
    </a>
      <!-- Bulk WhatsApp Card -->
    <a href="admin_whatsapp_bulk.php" class="block bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 p-4 group hover:scale-105">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-white/10 bg-opacity-20 backdrop-blur flex items-center justify-center">
          <i class="fa-brands fa-whatsapp text-white text-3xl"></i>
        </div>
        <i class="fa-solid fa-arrow-right text-white text-lg opacity-0 group-hover:opacity-100 transition"></i>
      </div>
      <h3 class="text-xl font-bold text-white mb-2">Bulk WhatsApp</h3>
      <p class="text-sm text-green-100">Send via MSG91</p>
    </a>
      <!-- Add Contact Card -->
    <a href="admin_add_contact.php" class="block bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 p-4 group hover:scale-105">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-white/10 bg-opacity-20 backdrop-blur flex items-center justify-center">
          <i class="fa-solid fa-address-book text-white text-2xl"></i>
        </div>
        <i class="fa-solid fa-arrow-right text-white text-lg opacity-0 group-hover:opacity-100 transition"></i>
      </div>
      <h3 class="text-xl font-bold text-white mb-2">Add Contact</h3>
      <p class="text-sm text-orange-100">Manual or Excel Import</p>
    </a>
      <!-- settin  -->
      <a href="admin_setting.php" class="block bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 p-4 group hover:scale-105">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-white/10 bg-opacity-20 backdrop-blur flex items-center justify-center">
          <i class="fa-solid fa-gear text-white text-3xl"></i>
        </div>
        <i class="fa-solid fa-arrow-right text-white text-lg opacity-0 group-hover:opacity-100 transition"></i>
      </div>
      <h3 class="text-xl font-bold text-white mb-2">Setting</h3>
      <p class="text-sm text-indigo-100">Set matrimony profile fee</p>
    </a>
      <!-- Anchor Applications Management Card -->
    <a href="admin_anchor_applications" class="block bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 p-4 group hover:scale-105">
      <div class="flex items-center justify-between mb-4">
        <div class="w-14 h-14 rounded-lg bg-white/10 bg-opacity-20 backdrop-blur flex items-center justify-center">
          <i class="fa-solid fa-microphone-lines text-white text-2xl"></i>
        </div>
        <i class="fa-solid fa-arrow-right text-white text-lg opacity-0 group-hover:opacity-100 transition"></i>
      </div>
      <h3 class="text-xl font-bold text-white mb-2">Anchor Applications</h3>
      <p class="text-sm text-orange-100">Approve/Reject news anchors</p>
    </a>

  </div>
</main>
