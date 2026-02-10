<?php
include("connection.php");
include("header.php");

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_email){
    echo "<div class='text-center text-red-500 mt-10'>Please login to continue.</div>";
    exit;
}

// Current user's marriage profile ID
$user_profile = $con->query("
    SELECT mp.id AS profile_id
    FROM tbl_marriage_profiles mp
    INNER JOIN tbl_members m ON m.id = mp.user_id
    WHERE m.email='$user_email' LIMIT 1
")->fetch_assoc();

$my_profile_id = $user_profile['profile_id'] ?? 0;


// Profile ID to view
$profile_id = intval($_GET['id'] ?? 0);
if(!$profile_id){
    echo "<div class='text-center text-red-500 mt-10'>Invalid profile!</div>";
    exit;
}

// Fetch target profile
$profile_q = $con->query("
    SELECT *, TIMESTAMPDIFF(YEAR, STR_TO_DATE(dob,'%Y-%m-%d'), CURDATE()) AS age
    FROM tbl_marriage_profiles
    WHERE id='$profile_id' LIMIT 1
");

if(!$profile_q || $profile_q->num_rows==0){
    echo "<div class='text-center text-gray-500 mt-10'>Profile not found.</div>";
    exit;
}

$profile = $profile_q->fetch_assoc();
$photo = !empty($profile['photo']) ? "uploads/photo/".$profile['photo'] : "https://via.placeholder.com/150";

// ✅ Check proposal status (sender → receiver or receiver → sender)
$status_check = $con->query("
    SELECT status 
    FROM tbl_proposals 
    WHERE (sender_id='$my_profile_id' AND receiver_id='$profile_id')
       OR (sender_id='$profile_id' AND receiver_id='$my_profile_id')
    ORDER BY id DESC
    LIMIT 1
");

$proposal_status = null;
if($status_check && $status_check->num_rows>0){
    $row = $status_check->fetch_assoc();
    $proposal_status = strtolower($row['status']); // pending / accepted / rejected / friend
}
?>

<main class="flex-1 px-2 md:px-10 py-15 bg-white md:ml-20 mb-13 md:mb-0">
  <div class="glass rounded-2xl shadow-2xl border border-orange-200 p-6 md:p-10 w-full max-w-7xl mx-auto flex flex-col items-center">

    <!-- Profile Photo -->
    <div class="w-28 h-28 md:w-36 md:h-36 p-1 flex items-center justify-center mb-3 relative">
      <img onclick="openImgModal('<?= $photo ?>')" 
     src="<?= $photo ?>" 
     class="w-24 h-24 md:w-32 md:h-32 object-cover rounded-full border-4 border-white shadow-xl cursor-pointer" />

    </div>

    <!-- Name + Status -->
    <h2 class="font-extrabold text-2xl md:text-3xl text-orange-700 mb-1 text-center">
        <?= htmlspecialchars($profile['full_name']) ?>
    </h2>
    <div class="text-orange-500 font-semibold text-lg md:text-xl mb-2 text-center">
        <?= htmlspecialchars($profile['status']) ?> | <?= htmlspecialchars($profile['gender']) ?>
    </div>

    <!-- Basic Info -->
    <div class="flex flex-wrap gap-3 justify-center mb-3 w-full">
        <span class="bg-orange-200 text-orange-700 rounded-md px-3 py-1 font-medium flex items-center gap-1 text-sm md:text-base">
            <i class="fa fa-calendar-day"></i> <?= $profile['age'] ?> yrs
        </span>
        <span class="bg-orange-200 text-orange-700 rounded-md px-3 py-1 font-medium flex items-center gap-1 text-sm md:text-base">
            <i class="fa fa-graduation-cap"></i> <?= htmlspecialchars($profile['education']) ?>
        </span>
        <span class="bg-orange-200 text-orange-700 rounded-md px-3 py-1 font-medium flex items-center gap-1 text-sm md:text-base">
            <i class="fa fa-briefcase"></i> <?= htmlspecialchars($profile['occupation'] ?? 'N/A') ?>
        </span>
        <span class="bg-orange-200 text-orange-700 rounded-md px-3 py-1 font-medium flex items-center gap-1 text-sm md:text-base">
            <i class="fa fa-location-dot"></i> <?= htmlspecialchars($profile['city']) ?>
        </span>
        <span class="bg-orange-200 text-orange-700 rounded-md px-3 py-1 font-medium flex items-center gap-1 text-sm md:text-base">
            <i class="fa fa-users"></i> <?= htmlspecialchars($profile['caste']) ?>
        </span>
    </div>

    <!-- About -->
    <div class="w-full mt-2 bg-orange-50 border border-orange-100 rounded-lg px-3 md:px-4 py-3 md:py-4 text-gray-700 text-sm md:text-base shadow">
        <div class="flex gap-2 items-center mb-2 text-orange-600 font-bold">
            <i class="fa fa-info-circle"></i> About
        </div>
        <div>
            <?= nl2br(htmlspecialchars($profile['about'] ?? 'No details provided.')) ?>
        </div>
    </div>

    <!-- View Full Details Button -->
    <button onclick="openProfileModal(<?= $profile_id ?>)" class="w-full mt-3 bg-white border border-orange-300 text-orange-700 font-bold py-3 rounded-xl hover:bg-orange-50 transition flex items-center justify-center gap-2 shadow-sm">
        <i class="fa fa-list-alt"></i> View Full Details
    </button>

    <!-- Community Profile -->

    <?php
// Fetch community profile from tbl_members
$uid = $profile['user_id'];

// If the community (member) is blocked, don't show the profile
$community_q = $con->query("SELECT * FROM tbl_members WHERE id='$uid' LIMIT 1");
$community = $community_q->fetch_assoc();
if(!$community || (isset($community['status']) && strtolower($community['status']) === 'blocked')){
    echo "<div class='text-center text-gray-500 mt-10'>Profile not found.</div>";
    exit;
}

// Use community photo or fallback marriage photo
$cp_photo = !empty($community['profile_photo']) 
            ? "uploads/photo/".$community['profile_photo'] 
            : $photo;
?>
<div class="w-full mt-8 bg-white rounded-2xl border border-orange-200 shadow px-4 py-4">

    <h3 class="text-xl font-bold text-orange-700 mb-3 flex items-center gap-2">
        <i class="fa fa-users"></i> Community Profile
    </h3>

    <a href="user_profile.php?id=<?= $community['id'] ?>" 
       class="group flex items-center justify-between w-full bg-orange-50 border border-orange-200 rounded-xl px-4 py-3 shadow hover:bg-orange-100 transition-all duration-300 hover:shadow-lg">

        <div class="flex items-center gap-3">

            <!-- PHOTO -->
            <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-orange-400 shadow">
                <img src="<?= $cp_photo ?>" 
                     alt="<?= htmlspecialchars($community['name']) ?>" 
                     class="w-full h-full object-cover">
            </div>

            <!-- TEXT INFO -->
            <div class="flex flex-col">

                <!-- NAME -->
                <div class="font-bold text-orange-700 text-lg leading-tight">
                    <?= htmlspecialchars($community['name']) ?>
                </div>

                <!-- CITY -->
                <?php if(!empty($community['city'])): ?>
                <span class="text-gray-600 text-sm">
                    <i class="fa fa-location-dot mr-1"></i> 
                    <?= htmlspecialchars($community['city']) ?>
                </span>
                <?php endif; ?>

                <!-- EDUCATION -->
                <?php if(!empty($community['education'])): ?>
                <span class="text-gray-600 text-sm">
                    <i class="fa fa-graduation-cap mr-1"></i> 
                    <?= htmlspecialchars($community['education']) ?>
                </span>
                <?php endif; ?>

                <!-- CASTE -->
                <?php if(!empty($community['cast'])): ?>
                <span class="text-gray-600 text-sm">
                    <i class="fa fa-users mr-1"></i> 
                    <?= htmlspecialchars($community['cast']) ?>
                </span>
                <?php endif; ?>

            </div>
        </div>

        <div class="text-orange-600 text-xl group-hover:translate-x-1 transition-transform">
            <i class="fa fa-arrow-right"></i>
        </div>

    </a>
</div>



    <!-- Action Button -->
    <div class="w-full flex flex-col md:flex-row gap-3 mt-7">
        <?php if(!$proposal_status || $proposal_status=='rejected'): ?>
            <!-- No proposal or rejected -->
            <a href="send_proposal.php?to=<?= $profile['id'] ?>&profile_id=<?= $profile_id ?>" 
               class="bg-orange-500 hover:bg-orange-600 text-white px-4 md:px-8 py-3 rounded-xl justify-center font-semibold shadow flex items-center gap-2 w-full transition text-base text-center">
               <i class="fa fa-paper-plane"></i> Send Proposal
            </a>
        <?php elseif($proposal_status=='pending'): ?>
            <!-- Proposal pending -->
            <button disabled class="bg-gray-300 text-gray-600 px-4 md:px-8 py-3 rounded-xl font-semibold shadow flex items-center justify-center gap-2 w-full cursor-not-allowed">
                <i class="fa fa-clock"></i> Requested
            </button>
        <?php elseif($proposal_status=='accepted' || $proposal_status=='friend'): ?>
            <!-- Proposal accepted -->
            <a href="message.php?sender_id=<?= $my_profile_id ?>&receiver_id=<?= $profile['id'] ?>" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 md:px-8 py-3 rounded-xl justify-center font-semibold shadow flex items-center gap-2 w-full transition text-base text-center">
                <i class="fa fa-comments"></i> Message
            </a>
        <?php endif; ?>
    </div>
  </div>
</main>

<div id="imgModal"
     class="fixed inset-0 bg-black/90 hidden items-center justify-center z-[999]">
  <img id="modalImage"
       class="max-w-full max-h-full rounded-lg shadow-2xl">
</div>

<!-- Profile Details Modal -->
<div id="profileModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-[100] p-4 overflow-y-auto">
  <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl relative flex flex-col max-h-[90vh]">
    
    <!-- Modal Header -->
    <div class="flex justify-between items-center p-4 border-b border-gray-100 bg-orange-50 rounded-t-2xl">
      <h3 class="text-xl font-bold text-orange-800"><i class="fa fa-user-circle mr-2"></i>Profile Details</h3>
      <button onclick="closeProfileModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-orange-200 text-orange-700 hover:bg-orange-600 hover:text-white transition">
        <i class="fa fa-times"></i>
      </button>
    </div>

    <!-- Modal Body -->
    <div id="profileModalBody" class="p-6 overflow-y-auto">
      <div class="text-center text-gray-500 py-10">
        <i class="fa fa-spinner fa-spin text-3xl text-orange-500 mb-3"></i>
        <p>Loading profile details...</p>
      </div>
    </div>

    <!-- Modal Footer -->
    <div class="p-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex justify-end gap-2">
      <button onclick="closeProfileModal()" class="px-5 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition">Close</button>
    </div>

  </div>
</div>

<script>
function openImgModal(src){
  document.getElementById("modalImage").src = src;
  document.getElementById("imgModal").classList.remove("hidden");
  document.getElementById("imgModal").classList.add("flex");
}

// click anywhere to close
document.getElementById("imgModal").addEventListener("click", function(){
  this.classList.add("hidden");
  this.classList.remove("flex");
});

/* ================= MODAL LOGIC ================= */
const profileModal = document.getElementById('profileModal');
const profileModalBody = document.getElementById('profileModalBody');

function openProfileModal(id){
    profileModal.classList.remove('hidden');
    profileModal.classList.add('flex');
    
    // Reset Content
    profileModalBody.innerHTML = `
      <div class="text-center text-gray-500 py-10">
        <i class="fa fa-spinner fa-spin text-3xl text-orange-500 mb-3"></i>
        <p>Loading details...</p>
      </div>`;

    // Fetch Content
    fetch('fetch_profile_details.php?id=' + id)
    .then(res => res.text())
    .then(html => {
        profileModalBody.innerHTML = html;
    })
    .catch(err => {
        profileModalBody.innerHTML = "<div class='text-red-500 text-center'>Failed to load details.</div>";
    });
}

function closeProfileModal(){
    profileModal.classList.add('hidden');
    profileModal.classList.remove('flex');
}

// Close on background click
profileModal.addEventListener('click', (e) => {
    if(e.target === profileModal){
        closeProfileModal();
    }
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