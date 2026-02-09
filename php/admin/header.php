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

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Sadhu vandana  - Admin Panel</title>
  <!-- Tailwind CDN -->
      <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <!-- FontAwesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen">

<!-- Header -->
<header class="bg-white shadow-md">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 bg-gradient-to-br from-orange-200 to-orange-300 rounded-lg flex items-center justify-center shadow-lg">
        <img src="../images/logo.png" alt="">
      </div>
      <div>
        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Admin Panel</h1>
        <p class="text-xs text-gray-500">Manage your community</p>
      </div>
    </div>
    
    <div class="flex items-center gap-3">
      <!-- Wallet Button -->
      <a href="#" class="hidden sm:flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition shadow">
        <i class="fa-solid fa-wallet"></i>
        <span class="text-sm font-medium">Wallet</span>
      </a>
      
      <!-- Profile Dropdown -->
      <div class="relative">
        <button onclick="toggleProfileMenu()" class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-500 flex items-center justify-center text-white font-bold shadow cursor-pointer hover:shadow-lg transition">
          A
        </button>
        
        <!-- Dropdown Menu -->
        <div id="profileMenu" class="absolute right-0 top-full mt-2 w-52 bg-white rounded-lg shadow-xl border border-gray-100 overflow-hidden transition-all duration-300 opacity-0 invisible">
          <div class="px-4 py-3 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Admin User</p>
            <p class="text-xs text-gray-500">admin@temple.com</p>
          </div>
          <a href="admin_change_password"  class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-orange-50 text-sm text-gray-700 transition">
            <i class="fa-solid fa-key text-orange-500"></i>
            <span>Change Password</span>
          </a>
          <a href="admin_logout" class="flex items-center gap-3 px-4 py-2.5 hover:bg-red-50 text-sm text-red-600 transition">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</header>



<!-- JavaScript -->
<script>
  var profileMenuOpen = false;

  // Profile menu toggle
  function toggleProfileMenu() {
    var menu = document.getElementById('profileMenu');
    
    if (profileMenuOpen) {
      menu.classList.add('opacity-0', 'invisible');
      menu.classList.remove('opacity-100', 'visible');
      profileMenuOpen = false;
    } else {
      menu.classList.remove('opacity-0', 'invisible');
      menu.classList.add('opacity-100', 'visible');
      profileMenuOpen = true;
    }
  }

  // Close profile menu when clicking outside
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
