<?php
include("connection.php");
include("auto_delete_stories.php");
session_start();

if(!isset($_SESSION['sadhu_user_id']) || empty($_SESSION['sadhu_user_id'])){
    
    if(isset($_COOKIE['sadhu_user_id']) && isset($_COOKIE['sadhu_user_name'])){
        // Cookie → session
        $_SESSION['sadhu_user_id'] = $_COOKIE['sadhu_user_id'];
        $_SESSION['sadhu_user_name'] = $_COOKIE['sadhu_user_name'];
    } else {
        echo "<script>window.location.href='login';</script>";
        exit;
    }
}

// -------------------------------------
// 🔥  CHECK USER BLOCK STATUS EVERY TIME

$uid = $_SESSION['sadhu_user_id'];

$stmt = $con->prepare("SELECT status FROM tbl_members WHERE email=? LIMIT 1");
$stmt->bind_param("s", $uid);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows == 1){
    $row = $res->fetch_assoc();

    if($row['status'] == "Blocked"){
        // Destroy Session + Cookies
        session_unset();
        session_destroy();

        setcookie("sadhu_user_id", "", time() - 3600, "/");
        setcookie("sadhu_user_name", "", time() - 3600, "/");

        echo "<script>alert('Your account has been blocked.'); window.location.href = 'login';</script>";
        exit;
    }
}
// -------------------------------------
// users online activity store 
if(isset($_SESSION['sadhu_user_id'])){
    $email = $_SESSION['sadhu_user_id'];
    $con->query("UPDATE tbl_members SET last_active = NOW() WHERE email='$email'");
}

// Get user info from session
$user_name = isset($_SESSION['sadhu_user_name']) ? $_SESSION['sadhu_user_name'] : 'Guest';
$user_id   = $_SESSION['sadhu_user_id'];

$profile_photo = '';

// Fetch user profile image from DB (assuming column name is 'profile_photo')
if($user_id){
    $stmt = "SELECT profile_photo FROM tbl_members WHERE email='$user_id' LIMIT 1";
    $res = mysqli_query($con,$stmt);
    if($res->num_rows){
        $row = $res->fetch_assoc();
        if(!empty($row['profile_photo'])){
            $file_path = 'uploads/photo/'.$row['profile_photo'];
            if(file_exists($file_path)){ // check if file exists
                $profile_photo = $file_path;
            }
        }
    }
}


// Assume $user_name is already set from session
$first_letters = '';

// Split name by space
$name_parts = explode(' ', trim($user_name));

// Take first letter of first word
if(isset($name_parts[0])){
    $first_letters .= strtoupper(substr($name_parts[0],0,1));
}

// Take first letter of second word if exists
if(isset($name_parts[1])){
    $first_letters .= strtoupper(substr($name_parts[1],0,1));
}

// Now $first_letters has 1 or 2 letters max
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- <title>Sadhu Vandana - Home</title> -->

  <!-- SEO Meta Tags -->
  <meta name="description"
    content="Sadhu Vandana Samaj Dashboard — Your digital home for news, stories, events, marriage profiles, and community updates. Connect, celebrate, and stay informed with verified news and real-time alerts.">
  <meta name="keywords"
    content="Samaj Dashboard, Sadhu Vandana, Community News, Marriage Profiles, Religious Events, Local News, Blood Donation, Announcements, Celebration, Support, Indian Community, Orange Theme, Social Bonds">
  <meta name="author" content="Sadhu Vandana Community">
  <meta name="robots" content="index, follow">
  <meta name="language" content="English">
  <meta name="revisit-after" content="7 days">
  <meta property="og:title" content="Samaj Dashboard — News, Events, and Community">
  <meta property="og:description"
    content="Get latest verified news, updates, and stories from Sadhu Vandana Samaj. Find marriage profiles, discover events, and grow your social bonds.">
  <meta property="og:image" content="https://yourdomain.com/assets/sadhu-vandana-share.jpg">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://yourdomain.com">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Samaj Dashboard — News & Events">
  <meta name="twitter:description"
    content="Sadhu Vandana's digital dashboard for news, stories, and vibrant community updates.">
  <meta name="twitter:image" content="https://yourdomain.com/assets/sadhu-vandana-share.jpg">
  <?php
    // Use per-page title if provided, otherwise infer from script name
    if(!empty($page_title)){
      $full_title = htmlspecialchars($page_title) . ' — Sadhu Vandana';
    } else {
      $script = basename($_SERVER['PHP_SELF'], '.php');
      $inferred = ucwords(str_replace(['_','-'], ' ', $script));
      $full_title = $inferred . ' — Sadhu Vandana';
    }
  ?>
  <title>
    <?php echo $full_title; ?>
  </title>
  <!-- TailwindCSS CDN -->
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Google Fonts: Roboto -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
  <!-- language script start -->

 
  <style>
    body {
      font-family: 'Roboto', sans-serif;
    }

    /* Hide scrollbar but allow scrolling */
    aside::-webkit-scrollbar {
      display: none;
      /* Chrome, Safari, Opera */
    }

    aside {
      -ms-overflow-style: none;
      /* IE and Edge */
      scrollbar-width: none;
      /* Firefox */
    }

    /* Hide horizontal scrollbar */
    .mob-scroll::-webkit-scrollbar {
      display: none;
    }

    .mob-scroll {
      scrollbar-width: none;
      -ms-overflow-style: none;
    }

    .goog-te-banner-frame.skiptranslate {
      display: none !important;
    }

    body {
      top: 0px !important;
    }

    .skiptranslate {
      display: none !important;
    }

    .goog-te-banner-frame.skiptranslate {
      display: none !important;
    }

    body {
      top: 0px !important;
    }

    .skiptranslate {
      display: none !important;
    }
  </style>

</head>

<body class="bg-white min-h-screen flex flex-col ">
  
<!-- Top Navbar start -->
<nav
  class="fixed top-0 left-0 w-full z-50 flex items-center justify-between px-3 py-1 bg-white shadow-sm border-b border-orange-300">

  <!-- LOGO + CLOCK -->
  <div class="flex items-center gap-3">
    <img src="images/logo.png" class="w-10" alt="">

    <!-- ✅ CLOCK + DATE (NOW VISIBLE ON MOBILE TOO) -->
    <div class="flex flex-col leading-tight cursor-pointer" onclick="openCalendar()">
      <div id="liveClock"
        class="text-xs sm:text-sm font-bold text-orange-700 flex items-center gap-1">
        <i class="fa-regular fa-clock"></i> --:--
      </div>

      <div id="liveDate"
        class="text-[10px] sm:text-[11px] text-gray-500 flex items-center gap-1">
        <i class="fa-regular fa-calendar"></i> -- ---
      </div>
    </div>
  </div>

  <div class="flex items-center gap-4 relative">

    <!-- NOTIFICATION -->
    <button onclick="openNotificationModal()" class="relative">
      <i class="fa-solid fa-bell text-orange-600 text-xl sm:text-2xl"></i>
      <span id="notifCount"
        class="absolute -top-2 -right-2 bg-red-600 text-white text-xs 
        font-bold px-1.5 py-0.5 rounded-full hidden"></span>
    </button>

    <!-- PROFILE -->
    <div class="relative">
      <button id="profileBtn"
        class="w-9 h-9 rounded-full overflow-hidden border-2 border-orange-400 flex items-center justify-center bg-orange-200 text-white font-bold">
        <?php if($profile_photo){ ?>
          <img src="<?= $profile_photo ?>" class="w-full h-full object-cover" />
        <?php } else { ?>
          <?= $first_letters ?>
        <?php } ?>
      </button>

      <div id="profileDropdown"
        class="hidden absolute right-0 mt-2 w-60 bg-white border border-orange-200 rounded-lg shadow-lg z-50">
        <a href="change_password" class="block px-4 py-2 text-orange-700 hover:bg-orange-100">
          Change Password
        </a>
        <a href="logout" class="block px-4 py-2 text-orange-700 hover:bg-orange-100">
          Logout
        </a>
      </div>
    </div>

  </div>
</nav>

<!-- ✅ HIDDEN CALENDAR INPUT -->
<input type="date" id="hiddenCalendar" class="hidden" />



  <!-- Main Layout -->
 <div class="flex flex-1 pt-4" id="pageContent">

  <!-- side navbar start -->
  <aside
  class="hidden md:flex flex-col justify-center w-20 fixed top-0 left-0 h-[100vh] py-30 items-center gap-4 border-r-2 border-orange-200">


    <!-- ✅ TOP CENTER ICONS (SAME AS BEFORE) -->
    <div class="flex flex-col items-center gap-4">

      <a href="index" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
        <i class="fa-solid fa-house text-2xl mb-1"></i>
        <span class="text-xs">Home</span>
      </a>

      <a href="profile" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
        <i class="fa-solid fa-user text-2xl mb-1"></i>
        <span class="text-xs">Profile</span>
      </a>

      <a href="news" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
        <i class="fa-solid fa-newspaper text-2xl mb-1"></i>
        <span class="text-xs">News</span>
      </a>

      <a href="marraige" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
        <i class="fa-solid fa-ring text-2xl mb-1"></i>
        <span class="text-xs">Marriage</span>
      </a>

    </div>

    <!-- ✅ GRID + DROPDOWN (BOTTOM CENTER, SAME POSITION FEEL) -->
    <div class="relative flex flex-col items-center">


      <!-- GRID ICON -->
      <button id="menuToggle"
        class="flex flex-col items-center text-orange-500 hover:text-orange-600 focus:outline-none">
        <i class="fa-solid fa-grip text-2xl mb-1"></i>
        <span class="text-xs">More</span>
      </button>

      <!-- ✅ DROPDOWN: ONLY  ITEMS -->
      <div id="menuBox"
  class="hidden absolute left-full bottom-0 ml-7
         bg-white border border-orange-200 rounded-xl shadow-lg
         flex flex-col items-center gap-3 px-4 py-3 z-50">


        <a href="gallery" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
          <i class="fa-solid fa-images text-xl"></i>
          <span class="text-[11px]">Gallery</span>
        </a>
        <a href="temple" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
          <i class="fa-solid fa-gopuram text-xl"></i>
          <span class="text-[11px]">Temple</span>
        </a>

        <a href="branch" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
          <i class="fa-solid fa-sitemap text-xl"></i>
          <span class="text-[11px]">Branch</span>
        </a>

        <a href="shok_sanvedana" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
          <i class="fa-solid fa-hands-praying text-xl"></i>
          <span class="text-[11px]"> Shok Sandesh</span>
        </a>
        <a href="jobs_education"
  class="flex flex-col items-center text-orange-500 hover:text-orange-600">
  <i class="fa-solid fa-briefcase text-xl"></i>
  <span class="text-[11px]">Jobs & Education</span>
</a>


      </div>
    </div>

  </aside>
  <!-- side navbar end -->

</div>
<!-- ✅ Mobile Navbar -->
<nav class="fixed bottom-0 left-0 w-full md:hidden bg-orange-500 shadow-lg z-50">

  <div class="grid grid-cols-5 text-center items-center">

    <a href="index" class="py-2 flex flex-col items-center text-white hover:bg-orange-600">
      <i class="fa-solid fa-house text-lg"></i>
      <span class="text-[11px] leading-none mt-1">Home</span>
    </a>

    <a href="profile" class="py-2 flex flex-col items-center text-white hover:bg-orange-600">
      <i class="fa-solid fa-user text-lg"></i>
      <span class="text-[11px] leading-none mt-1">Profile</span>
    </a>

    <a href="news" class="py-2 flex flex-col items-center text-white hover:bg-orange-600">
      <i class="fa-solid fa-newspaper text-lg"></i>
      <span class="text-[11px] leading-none mt-1">News</span>
    </a>

    <a href="marraige" class="py-2 flex flex-col items-center text-white hover:bg-orange-600">
      <i class="fa-solid fa-ring text-lg"></i>
      <span class="text-[11px] leading-none mt-1">Marriage</span>
    </a>

    <!-- ✅ GRID ICON -->
    <button id="mobileMenuToggle"
      class="py-2 flex flex-col items-center text-white hover:bg-orange-600 focus:outline-none">
      <i class="fa-solid fa-grip text-lg"></i>
      <span class="text-[11px] leading-none mt-1">More</span>
    </button>

  </div>

  <!-- ✅ MOBILE DROPDOWN (ONLY 3 ITEMS) -->
  <div id="mobileMenuBox"
  class="hidden absolute bottom-14 left-2 right-2 mx-auto
         bg-white border border-orange-300 rounded-xl shadow-lg
         flex justify-around px-4 py-3 z-50">

    <a href="gallery" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
      <i class="fa-solid fa-images text-xl"></i>
      <span class="text-[11px]">Gallery</span>
    </a>
    <a href="temple" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
      <i class="fa-solid fa-gopuram text-xl"></i>
      <span class="text-[11px]">Temple</span>
    </a>

    <a href="branch" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
      <i class="fa-solid fa-sitemap text-xl"></i>
      <span class="text-[11px]">Branch</span>
    </a>

    <a href="shok_sanvedana" class="flex flex-col items-center text-orange-500 hover:text-orange-600">
      <i class="fa-solid fa-hands-praying text-xl"></i>
      <span class="text-[11px]">Shok Sandesh</span>
    </a>
    <a href="jobs_education"
  class="flex flex-col items-center text-orange-500">
  <i class="fa-solid fa-briefcase text-xl"></i>
  <span class="text-[11px]">Jobs & Education</span>
</a>

  </div>
</nav>


<script>
const mobileToggle = document.getElementById("mobileMenuToggle");
const mobileBox = document.getElementById("mobileMenuBox");

mobileToggle.addEventListener("click", function (e) {
  e.stopPropagation();
  mobileBox.classList.toggle("hidden");
});

document.addEventListener("click", function () {
  mobileBox.classList.add("hidden");
});
</script>



<script>
  const menuToggle = document.getElementById("menuToggle");
  const menuBox = document.getElementById("menuBox");

  menuToggle.addEventListener("click", function (e) {
    e.stopPropagation();
    menuBox.classList.toggle("hidden");
  });

  document.addEventListener("click", function () {
    menuBox.classList.add("hidden");
  });
</script>





    <script>
      const btn = document.getElementById('profileBtn');
      const drop = document.getElementById('profileDropdown');
      btn.onclick = () => drop.classList.toggle('hidden');
      document.addEventListener('click', function (e) {
        if (!btn.contains(e.target) && !drop.contains(e.target)) {
          drop.classList.add('hidden');
        }
      });
    </script>
    <script>
      // Message dropdown toggle
      const msgBtn = document.getElementById('messageBtn');
      const msgDropdown = document.getElementById('msgDropdown');
      msgBtn.onclick = (e) => { e.stopPropagation(); msgDropdown.classList.toggle('hidden'); };
      document.body.addEventListener('click', (e) => {
        if (!msgDropdown.contains(e.target) && !msgBtn.contains(e.target)) msgDropdown.classList.add('hidden');
      });
      // Chat modal logic
      function openChat(name) {
        document.getElementById('chatModal').classList.remove("hidden");
        if (name === "Shilpi Verma") {
          document.getElementById('chatName').textContent = "Shilpi Verma";
          document.getElementById('chatAvatar').src = "https://randomuser.me/api/portraits/women/47.jpg";
        } else {
          document.getElementById('chatName').textContent = "Rohit Sharma";
          document.getElementById('chatAvatar').src = "https://randomuser.me/api/portraits/men/45.jpg";
        }
        msgDropdown.classList.add('hidden');
      }
      function closeChat() { document.getElementById('chatModal').classList.add("hidden"); }
    </script>


    <!-- NOTIFICATION MODAL -->
    <div id="notifModal" class="fixed inset-0 bg-black/60 hidden flex justify-center items-start pt-20 z-50">

      <div class="bg-white w-96 max-h-[80vh] rounded-xl shadow-xl p-4 overflow-y-auto">

        <div class="flex justify-between items-center mb-3">
          <h2 class="text-lg font-bold text-gray-800">Notifications</h2>
          <button onclick="closeNotificationModal()">
            <i class="fa-solid fa-xmark text-xl text-gray-600"></i>
          </button>
        </div>

        <div id="notifList" class="space-y-3">
          <!-- Loaded notifications -->
        </div>

      </div>
    </div>



    <script>
      function openNotificationModal() {
        document.getElementById("notifModal").classList.remove("hidden");
        loadNotifications();
      }

      function closeNotificationModal() {
        document.getElementById("notifModal").classList.add("hidden");
      }

      // 🔥 Load Notification List
      function loadNotifications() {
        fetch("get_notification.php")
          .then(res => res.json())
          .then(data => {

            let html = "";

            data.forEach(n => {

              let img = "uploads/photo/" + (n.profile && n.profile !== "" ? n.profile : "default.png");

              html += `
    <div onclick="openMessage(${n.sender_id}, ${n.receiver_id})"
"
        class="flex items-center gap-3 p-2 rounded-lg border cursor-pointer hover:bg-orange-50">

        <img src="${img}" 
             class="w-10 h-10 rounded-full object-cover border shadow">

        <div class="flex-1">
            <p class="font-semibold text-gray-800">${n.name}</p>
            <p class="text-xs text-gray-500 line-clamp-1">${n.message}</p>

            <span class="text-[11px] text-blue-600 font-semibold">
                ${n.unread_count} message(s)
            </span>
        </div>

        <span class="text-[10px] text-gray-400">${n.date}</span>
    </div>`;

            });

            document.getElementById("notifList").innerHTML = html;
          });
      }



      // 🔥 Redirect to chat
      function openMessage(sender_id, receiver_id) {
        window.location.href = "message.php?sender_id=" + receiver_id + "&receiver_id=" + sender_id;
      }



      // 🔥 Update unread count badge
      function updateNotifCount() {
        fetch("notification_count.php")
          .then(res => res.text())
          .then(count => {

            let badge = document.getElementById("notifCount");

            if (parseInt(count) > 0) {
              badge.innerText = count;
              badge.classList.remove("hidden");
            } else {
              badge.classList.add("hidden");
            }
          });
      }

      // Auto refresh every 4 seconds
      setInterval(updateNotifCount, 4000);
      updateNotifCount();
    </script>
 
<!-- clock and calender script  -->
<script>
function updateClock() {
  const now = new Date();

  let hours = now.getHours();
  let minutes = now.getMinutes();
  let seconds = now.getSeconds();
  let ampm = hours >= 12 ? 'PM' : 'AM';

  hours = hours % 12;
  hours = hours ? hours : 12;
  minutes = minutes < 10 ? '0' + minutes : minutes;

  const timeString = hours + ":" + minutes + " " + ampm;

  const options = { day: '2-digit', month: 'short', year: 'numeric' };
  const dateString = now.toLocaleDateString('en-IN', options);

  document.getElementById("liveClock").innerHTML =
    '<i class="fa-regular fa-clock"></i> ' + timeString;

  document.getElementById("liveDate").innerHTML =
    '<i class="fa-regular fa-calendar"></i> ' + dateString;
}

setInterval(updateClock, 1000);
updateClock();

/* ✅ OPEN CALENDAR ON CLICK */
function openCalendar(){
  document.getElementById("hiddenCalendar").showPicker();
}
</script>
 
