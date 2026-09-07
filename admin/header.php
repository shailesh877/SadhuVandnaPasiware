<?php
//session_start();
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
    echo "<script>window.location.href = 'admin_login';</script>";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Linkup - Admin Dashboard</title>
  <link rel="icon" type="image/png" href="../images/logo.png"/>
  <!-- Tailwind CDN -->
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <!-- FontAwesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <style>
    @media (min-width: 1024px) {
      body { padding-left: 16rem; } /* lg:pl-64 */
    }
    
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        color: #4b5563; /* gray-600 */
        transition: all 0.2s;
    }
    .sidebar-link:hover {
        background-color: #fff7ed; /* orange-50 */
        color: #ea580c; /* orange-600 */
    }
    .sidebar-link.active {
        background-color: #f97316; /* orange-500 */
        color: #ffffff;
        box-shadow: 0 4px 6px -1px rgba(249, 115, 22, 0.2);
    }
    .sidebar-section {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #9ca3af; /* gray-400 */
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
        padding-left: 1rem;
    }
    
    /* Global Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: #fff7ed; /* Tailwind orange-50 */
    }
    ::-webkit-scrollbar-thumb {
        background: linear-gradient(to bottom, #f97316, #ef4444); /* Tailwind orange-500 to red-500 */
        border-radius: 10px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(to bottom, #ea580c, #dc2626);
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans">

<!-- Sidebar -->
<aside id="adminSidebar" class="fixed inset-y-0 left-0 w-64 bg-white shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 flex flex-col h-screen overflow-y-auto">
    
    <!-- Sidebar Header -->
    <div class="px-6 py-4 flex items-center justify-between sticky top-0 bg-white z-10 border-b border-gray-50">
        <div class="flex items-center gap-2">
            <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-orange-500 rounded-lg flex items-center justify-center shadow-md">
                <!-- <i class="fa-solid fa-hands-praying text-white text-sm"></i> -->
                 <img src="../images/logo.png" alt="Logo" class="w-full h-full object-contain">
            </div>
            <span class="text-xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-orange-600 to-red-500">
                Linkup
            </span>
        </div>
        <button onclick="toggleSidebar()" class="lg:hidden w-8 h-8 flex items-center justify-center rounded-full bg-gray-50 text-gray-500 hover:bg-gray-100">
            <i class="fa-solid fa-times text-sm"></i>
        </button>
    </div>

    <!-- Sidebar Links -->
    <nav class="flex-1 px-4 py-4 space-y-1 mb-6">
        <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
        
        <a href="index.php" class="sidebar-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie w-5 text-center"></i>
            <span class="font-medium text-sm">Dashboard</span>
        </a>

        <div class="sidebar-section">Community</div>
        <a href="admin_new_registration.php" class="sidebar-link <?= ($currentPage == 'admin_new_registration.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-user-plus w-5 text-center"></i>
            <span class="font-medium text-sm">Pending Approvals</span>
        </a>
        <a href="admin_all_community_member.php" class="sidebar-link <?= ($currentPage == 'admin_all_community_member.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-users w-5 text-center"></i>
            <span class="font-medium text-sm">All Members</span>
        </a>
        <a href="admin_block_user.php" class="sidebar-link <?= ($currentPage == 'admin_block_user.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-user-slash w-5 text-center"></i>
            <span class="font-medium text-sm">Blocked Users</span>
        </a>
        <a href="admin_anchor_applications.php" class="sidebar-link <?= ($currentPage == 'admin_anchor_applications.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-microphone-lines w-5 text-center"></i>
            <span class="font-medium text-sm">Anchor Applications</span>
        </a>

        <div class="sidebar-section">Content</div>
        <a href="admin_post.php" class="sidebar-link <?= ($currentPage == 'admin_post.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-photo-film w-5 text-center"></i>
            <span class="font-medium text-sm">Manage Posts</span>
        </a>
        <a href="admin_reported_posts.php" class="sidebar-link <?= ($currentPage == 'admin_reported_posts.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-flag w-5 text-center"></i>
            <span class="font-medium text-sm">Reported Posts</span>
        </a>
        <a href="add_views_news.php" class="sidebar-link <?= ($currentPage == 'add_views_news.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-newspaper w-5 text-center"></i>
            <span class="font-medium text-sm">News Updates</span>
        </a>
        <a href="admin_jobs_education.php" class="sidebar-link <?= ($currentPage == 'admin_jobs_education.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-chalkboard-user w-5 text-center"></i>
            <span class="font-medium text-sm">Jobs & Education</span>
        </a>
        <a href="admin_gallery.php" class="sidebar-link <?= ($currentPage == 'admin_gallery.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-image w-5 text-center"></i>
            <span class="font-medium text-sm">Gallery</span>
        </a>
        <a href="upload_music.php" class="sidebar-link <?= ($currentPage == 'upload_music.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-music w-5 text-center"></i>
            <span class="font-medium text-sm">Music Management</span>
        </a>

        <div class="sidebar-section">Network</div>
        <a href="create_temple.php" class="sidebar-link <?= ($currentPage == 'create_temple.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-place-of-worship w-5 text-center"></i>
            <span class="font-medium text-sm">Temples</span>
        </a>
        <a href="create_branch.php" class="sidebar-link <?= ($currentPage == 'create_branch.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-code-branch w-5 text-center"></i>
            <span class="font-medium text-sm">Branches</span>
        </a>

        <div class="sidebar-section">Tools</div>
        <a href="admin_wallet.php" class="sidebar-link <?= ($currentPage == 'admin_wallet.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-wallet w-5 text-center"></i>
            <span class="font-medium text-sm">Wallet & Revenue</span>
        </a>
        <a href="admin_whatsapp_bulk.php" class="sidebar-link <?= ($currentPage == 'admin_whatsapp_bulk.php') ? 'active' : '' ?>">
            <i class="fa-brands fa-whatsapp w-5 text-center"></i>
            <span class="font-medium text-sm">Bulk WhatsApp</span>
        </a>
        <a href="admin_add_contact.php" class="sidebar-link <?= ($currentPage == 'admin_add_contact.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-address-book w-5 text-center"></i>
            <span class="font-medium text-sm">Contacts</span>
        </a>
        <a href="admin_festival_frames.php" class="sidebar-link <?= ($currentPage == 'admin_festival_frames.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-wand-magic-sparkles w-5 text-center"></i>
            <span class="font-medium text-sm">Festival Frames</span>
        </a>

        <div class="sidebar-section">System</div>
        <a href="admin_setting.php" class="sidebar-link <?= ($currentPage == 'admin_setting.php') ? 'active' : '' ?>">
            <i class="fa-solid fa-gear w-5 text-center"></i>
            <span class="font-medium text-sm">Global Settings</span>
        </a>
    </nav>
</aside>

<!-- Overlay for mobile sidebar -->
<div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-gray-900/30 backdrop-blur-sm z-40 hidden lg:hidden transition-opacity"></div>

<!-- Top Header (Main content area) -->
<header class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-30">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
    
    <div class="flex items-center gap-3">
        <!-- Mobile Menu Toggle -->
        <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-100 transition">
            <i class="fa-solid fa-bars text-lg"></i>
        </button>
        <!-- Page Title Context -->
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-gray-800">Admin Console</h1>
            <p class="text-[10px] sm:text-xs text-gray-500">Manage your platform</p>
        </div>
    </div>
    
    <div class="flex items-center gap-4">
      
      <!-- Profile Dropdown -->
      <div class="relative">
        <button onclick="toggleProfileMenu()" class="flex items-center gap-2 hover:bg-gray-50 px-2 py-1.5 rounded-lg transition cursor-pointer">
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center text-white font-bold shadow-sm">
              <i class="fa-solid fa-user-shield text-sm"></i>
            </div>
            <div class="hidden sm:block text-left">
                <p class="text-xs font-bold text-gray-800 leading-tight">Admin User</p>
                <p class="text-[10px] text-gray-500 leading-tight">Super Admin</p>
            </div>
            <i class="fa-solid fa-chevron-down text-xs text-gray-400 ml-1"></i>
        </button>
        
        <!-- Dropdown Menu -->
        <div id="profileMenu" class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden transition-all duration-200 origin-top-right transform scale-95 opacity-0 invisible">
          <a href="admin_change_password.php" class="w-full flex items-center gap-3 px-4 py-3 hover:bg-orange-50 hover:text-orange-600 text-sm font-medium text-gray-600 transition">
            <i class="fa-solid fa-key w-4 text-center"></i>
            <span>Change Password</span>
          </a>
          <div class="h-px bg-gray-100 w-full"></div>
          <a href="admin_logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 hover:text-red-600 text-sm font-medium text-red-500 transition">
            <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>
            <span>Sign Out</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- JavaScript -->
<script>
  var profileMenuOpen = false;
  var sidebarOpen = false;

  // Profile menu toggle
  function toggleProfileMenu() {
    var menu = document.getElementById('profileMenu');
    
    if (profileMenuOpen) {
      menu.classList.add('opacity-0', 'invisible', 'scale-95');
      menu.classList.remove('opacity-100', 'visible', 'scale-100');
      profileMenuOpen = false;
    } else {
      menu.classList.remove('opacity-0', 'invisible', 'scale-95');
      menu.classList.add('opacity-100', 'visible', 'scale-100');
      profileMenuOpen = true;
    }
  }

  // Sidebar toggle for mobile
  function toggleSidebar() {
      var sidebar = document.getElementById('adminSidebar');
      var overlay = document.getElementById('sidebarOverlay');
      
      if(sidebarOpen) {
          sidebar.classList.add('-translate-x-full');
          overlay.classList.add('hidden');
          sidebarOpen = false;
      } else {
          sidebar.classList.remove('-translate-x-full');
          overlay.classList.remove('hidden');
          sidebarOpen = true;
      }
  }

  // Close menus when clicking outside
  document.addEventListener('click', function(event) {
    var profileButton = event.target.closest('button[onclick="toggleProfileMenu()"]');
    var menu = document.getElementById('profileMenu');
    
    if (!profileButton && !menu.contains(event.target) && profileMenuOpen) {
      toggleProfileMenu();
    }
  });

  // Toggle password visibility
  function togglePassword(inputId) {
    var input = document.getElementById(inputId);
    var icon = event.target.closest('button').querySelector('i');
    
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  }
</script>
