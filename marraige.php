<?php
include("connection.php");
include("header.php");

$user_mobile = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_mobile) die("Unauthorized");
$user = $con->query("SELECT id FROM tbl_members WHERE mobile='$user_mobile'")->fetch_assoc();
$logged_id = $user['id'];
// echo $logged_id;
$query="SELECT id from tbl_marriage_profiles WHERE user_id='$logged_id'";
$profileExists = $con->query($query)->num_rows > 0;
$receiver_id = $profileExists ? $con->query($query)->fetch_assoc()['id'] : 0;

// Count new requests received by logged user
$requestCountQuery = $con->query("
    SELECT COUNT(*) AS total 
    FROM tbl_proposals 
    WHERE receiver_id = '$receiver_id' AND status = 'pending'
");
$requestCount = $requestCountQuery->fetch_assoc()['total'];

?>

<main class="flex-1 px-2 md:px-10  bg-white md:ml-20  md:mb-0">




  <!-- Filters -->
  <section class="max-w-8xl mx-auto bg-white rounded-xl shadow-xl p-5 sticky top-10 md:top-15 z-40 flex flex-wrap md:flex-nowrap items-center gap-4">
    
    <!-- Mobile Toggle Button -->
    <div class="mb-3 flex flex-1 md:hidden justify-between items-center">
      <span class="text-lg font-bold text-orange-700">Filters</span>
      <button id="filterToggleBtn"
        class="bg-orange-500 text-white px-4 py-2 rounded-lg shadow hover:bg-orange-600 transition text-base font-semibold flex items-center">
        <i class="fa fa-sliders"></i> <span class="ml-2">Show</span>
      </button>
    </div>

    <!-- Filter Form -->
    <form id="filterForm" class="flex flex-wrap gap-4 items-center hidden md:flex-1" style="display:none;">
      <select name="gender" class="border rounded-lg px-4 py-2 text-lg border-orange-200 w-full md:w-36">
        <option value="">Gender</option>
        <option>Male</option>
        <option>Female</option>
      </select>
      <select name="age" class="border rounded-lg px-4 py-2 text-lg border-orange-200 w-full md:w-32">
        <option value="">Age</option>
        <option value="18-21">18-21</option>
        <option value="22-25">22-25</option>
        <option value="26-30">26-30</option>
        <option value="31-35">31-35</option>
      </select>
      <input type="text" name="city" placeholder="City" class="border-orange-200 border rounded-lg px-4 py-2 text-lg w-full md:w-36" />
      <input type="text" name="education" placeholder="Education (type...)" class="border-orange-200 border rounded-lg px-4 py-2 text-lg w-full md:w-44" />
      <button type="submit"
        class="bg-orange-600 hover:bg-orange-700 text-white font-bold text-lg px-8 py-2 rounded-lg shadow-md transition w-full md:w-auto">
        <i class="fa fa-search mr-2"></i>Search
      </button>
      
    </form>
     <div class="flex gap-3 mx-auto md:mx-0">

        <!-- SEND -->
        <a href="send_request.php"
            class="group flex flex-col items-center bg-white rounded-lg p-2 transition hover:bg-orange-50">

            <div class="w-10 h-10 flex items-center justify-center bg-orange-100 text-orange-600 rounded-lg text-md group-hover:bg-orange-600 group-hover:text-white transition">
                <i class="fa fa-paper-plane"></i>
            </div>

            <span class="text-orange-700 text-xs group-hover:text-orange-800 transition mt-1">
                Send
            </span>
        </a>

        <!-- REQUESTED -->
         <a href="view_request.php" class="relative group flex flex-col items-center bg-white rounded-lg p-2 transition hover:bg-orange-50">

                <!-- 🔥 Notification Badge -->
                <?php if($requestCount > 0) { ?>
                    <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow">
                        <?= $requestCount ?>
                    </span>
                <?php } ?>

                <div class="w-10 h-10 flex items-center justify-center bg-orange-100 text-orange-600 rounded-lg text-xl 
                group-hover:bg-orange-600 group-hover:text-white transition">
                    <i class="fa fa-envelope-open-text"></i>
                </div>
                <span class="text-orange-700 text-xs mt-1">Requested</span>
            </a>

        <!-- CONNECTED -->
        <a href="connected.php"
            class="group flex flex-col items-center bg-white rounded-lg p-2 transition hover:bg-orange-50">

            <div class="w-10 h-10 flex items-center justify-center bg-orange-100 text-orange-600 rounded-lg text-md group-hover:bg-orange-600 group-hover:text-white transition">
                <i class="fa fa-user-friends"></i>
            </div>

            <span class="text-orange-700 text-xs group-hover:text-orange-800 transition mt-1">
                Connected
            </span>
        </a>

    </div>
  </section>

  <!-- Profiles Grid -->
  <section id="profilesContainer" class="max-w-8xl mt-15 mx-auto pb-12">
    <div id="profilesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"></div>
    <div id="loader" class="text-center text-gray-500 mt-4 hidden">Loading more...</div>
  </section>
  
</main>

<script>
const filterBtn = document.getElementById('filterToggleBtn');
const filterForm = document.getElementById('filterForm');

filterBtn.addEventListener('click', () => {
  if (filterForm.style.display === 'none' || filterForm.style.display === '') {
    filterForm.style.display = 'flex';
    filterBtn.querySelector('span').innerText = 'Hide';
  } else {
    filterForm.style.display = 'none';
    filterBtn.querySelector('span').innerText = 'Show';
  }
});

// ✅ Load profiles dynamically with Infinite Scroll
let offset = 0;
const limit = 8;
let isLoading = false;
let hasMore = true;
let searchTimer;

function loadProfiles(isInitial = false) {
  if (isLoading || (!hasMore && !isInitial)) return;
  isLoading = true;
  document.getElementById('loader').classList.remove('hidden');

  if (isInitial) {
    offset = 0;
    hasMore = true;
    document.getElementById('profilesGrid').innerHTML = '';
  }

  const formData = new FormData(document.getElementById('filterForm'));
  formData.append('limit', limit);
  formData.append('offset', offset);

  fetch('fetch_profiles.php', {
    method: 'POST',
    body: formData
  })
    .then(res => res.text())
    .then(html => {
      const trimmedHTML = html.trim();
      if (trimmedHTML === "") {
        hasMore = false;
        if(isInitial) document.getElementById('profilesGrid').innerHTML = `<div class='text-center text-gray-500 w-full col-span-full mt-10'>No profiles found.</div>`;
      } else {
         // Check if the returned HTML is the specific "No profiles found" message for offset 0
         if (isInitial && trimmedHTML.includes('No profiles found')) {
              document.getElementById('profilesGrid').innerHTML = trimmedHTML;
              hasMore = false;
         } else {
             document.getElementById('profilesGrid').insertAdjacentHTML('beforeend', trimmedHTML);
             offset += limit;
         }
      }
    })
    .catch(err => {
        console.error(err);
      if(isInitial) document.getElementById('profilesGrid').innerHTML = `<div class='text-center text-red-500 w-full'>Error loading profiles.</div>`;
    })
    .finally(() => {
        isLoading = false;
        document.getElementById('loader').classList.add('hidden');
    });
}

// Live Search with Debounce
filterForm.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadProfiles(true), 500);
});
filterForm.addEventListener('change', () => loadProfiles(true));

document.getElementById('filterForm').addEventListener('submit', e => {
  e.preventDefault();
  loadProfiles(true);
});

// Infinite Scroll
window.addEventListener('scroll', () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
        loadProfiles();
    }
});

// Load on page start
window.addEventListener('load', () => {
  // ✅ Show filters by default on desktop
  if (window.innerWidth >= 768) {
    filterForm.style.display = 'flex';
  }
  loadProfiles(true);
});
</script>

<script>
// disable right click
document.addEventListener("contextmenu", e => e.preventDefault());

// disable drag
document.addEventListener("dragstart", e => e.preventDefault());

// disable ctrl keys
document.addEventListener("keydown", function(e){

    // Ctrl + S / U / P / C / X / A
    if (
        e.ctrlKey &&
        ['s','u','p','c','x','a'].includes(e.key.toLowerCase())
    ) {
        e.preventDefault();
    }

    // Print Screen
    if (e.key === "PrintScreen") {
        document.body.style.filter = "blur(10px)";
        setTimeout(() => {
            document.body.style.filter = "none";
        }, 2000);
    }

    // F12
    if (e.keyCode === 123) {
        e.preventDefault();
    }
});

// mobile screenshot detection (best possible)
document.addEventListener("visibilitychange", function(){
    if(document.hidden){
        document.body.style.filter = "blur(15px)";
    } else {
        document.body.style.filter = "none";
    }
});

// disable text selection
document.onselectstart = () => false;
</script>
