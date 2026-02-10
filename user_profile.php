<?php
include("header.php");
include("connection.php");


$user_id = intval($_GET['id'] ?? 0);
if(!$user_id) die("<div class='text-center p-10 text-red-600 text-xl font-bold'>Invalid user!</div>");

$user = $con->query("SELECT * FROM tbl_members WHERE id=$user_id")->fetch_assoc();
if(!$user) die("<div class='text-center p-10 text-red-600 text-xl font-bold'>User not found!</div>");

// Hide blocked users' profiles
// if(isset($user['status']) && strtolower($user['status']) === 'blocked'){
//   die("<div class='text-center p-10 text-red-600 text-xl font-bold'>User not found!</div>");
// }

$logged_email = $_SESSION['sadhu_user_id'] ?? '';
$logged_user = $con->query("SELECT id,email,profile_photo FROM tbl_members WHERE email='$logged_email'")->fetch_assoc();
$logged_id = $logged_user['id'] ?? 0;
$loged_profile_photo = $logged_user['profile_photo'] ?? '';
?>

<main class="flex-1 px-3 md:px-10 py-15 bg-white md:ml-20 mb-14 md:mb-0">

  <!-- 🖼️ Cover -->
  <?php if (!empty($user['cover_photo'])): ?>
  <div class="relative w-full h-52 md:h-64 rounded-xl overflow-hidden shadow">
    <img src="uploads/photo/<?php echo htmlspecialchars($user['cover_photo']); ?>" class="w-full h-full object-cover"
      onclick="openImageModal(this.src)">
  </div>
  <?php endif; ?>

  <!-- 👤 Profile -->
  <div class="bg-white rounded-xl shadow-lg border border-orange-200 mt-[-3rem] md:mt-[-4rem] relative z-10 px-6 py-5">
    <div class="flex flex-col md:flex-row items-center md:items-end gap-4">
      <img src="uploads/photo/<?php echo htmlspecialchars($user['profile_photo']); ?>"
        class="w-28 h-28 rounded-full border-4 border-orange-300 shadow-lg bg-white" onclick="openImageModal(this.src)">
      <div class="flex flex-col md:flex-row justify-between w-full md:items-end">
        <div>
          <h1 class="text-2xl font-bold text-orange-700">
            <?php echo htmlspecialchars($user['name']); ?>
          </h1>
          <?php if (!empty($user['about'])): ?>
          <p class="text-gray-700 italic mt-1">
            <?php echo htmlspecialchars($user['about']); ?>
          </p>
          <?php endif; ?>
          <p class="text-sm text-gray-500 mt-1">Joined on:
            <?php echo date("d M, Y", strtotime($user['date'])); ?>
          </p>
          <button id="moreBtn" class="text-sm text-orange-600 hover:underline mt-1">View More Details</button>
        </div>
        <?php if($logged_email === $user['email']): ?>
        <a href="edit_profile.php" class="bg-orange-500 text-white px-4 py-2 rounded-lg shadow hover:bg-orange-700">Edit
          Profile</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php
// FAMILY MEMBERS FROM tbl_family_members
$family_members = $con->query("
    SELECT * FROM tbl_family_members 
    WHERE user_id='$user_id' ORDER BY id DESC
");

// MARRIAGE PROFILE
$marriage_profile = $con->query("
    SELECT * FROM tbl_marriage_profiles 
    WHERE user_id='$user_id' LIMIT 1
")->fetch_assoc();
?>

  <!-- FAMILY & MARRIAGE SECTION -->
  <div class="bg-white mt-6 p-6 rounded-xl shadow-lg border border-orange-200">

    <h2 class="text-xl font-bold text-orange-700 mb-4 flex items-center gap-2">
      <i class="fa-solid fa-users"></i> Family & Marriage Information
    </h2>

    <div class="grid md:grid-cols-2 gap-6">

      <!-- FAMILY MEMBERS -->
      <div class="p-4 bg-orange-50 border border-orange-200 rounded-xl shadow-sm">
        <h3 class="text-lg font-bold text-orange-800 mb-3">
          <i class="fa fa-people-roof"></i> Family Members
        </h3>

        <?php if($family_members->num_rows > 0): ?>

        <!-- Scrollable Box -->
        <div class="flex flex-col gap-3 max-h-[350px] overflow-y-auto pr-2 custom-scroll">

          <?php while($fm = $family_members->fetch_assoc()): ?>
          <div class="flex gap-3 items-center p-2 bg-white rounded-lg border border-orange-100 shadow-sm">

            <img src="<?= !empty($fm['photo']) && file_exists('uploads/family/'.$fm['photo']) 
                        ? 'uploads/family/'.$fm['photo'] 
                        : 'https://via.placeholder.com/60?text=Photo' ?>"
              class="w-12 h-12 rounded-full border border-orange-300 object-cover cursor-pointer"
              onclick="openImageModal(this.src)" />

           <div class="flex-1 text-xs text-gray-700">

  <!-- BASIC INFO (ALWAYS VISIBLE) -->
  <div class="font-bold text-orange-700 text-sm">
    <?= htmlspecialchars($fm['name']) ?>
  </div>

  <div class="text-xs text-gray-600">
    <?= htmlspecialchars($fm['relation']) ?> | <?= htmlspecialchars($fm['gender']) ?>
  </div>

  <?php if(!empty($fm['dob'])): 
    $age = date_diff(date_create($fm['dob']), date_create('today'))->y; ?>
    <div class="text-xs text-gray-600">
      Age: <?= $age ?> Years
    </div>
  <?php endif; ?>

  <div class="text-xs bg-orange-200 text-orange-700 px-2 py-0.5 rounded-full inline-block mt-1">
    <?= htmlspecialchars($fm['marital_status']) ?>
  </div>

  <!-- READ MORE CONTENT -->
  <div class="hidden mt-2 space-y-1 family-more">

    <?php if($fm['dob']): ?>
      <div>DOB: <?= date("d-m-Y", strtotime($fm['dob'])) ?></div>
    <?php endif; ?>

    <?php if($fm['height']): ?>
      <div>Height: <?= htmlspecialchars($fm['height']) ?></div>
    <?php endif; ?>

    <?php if($fm['weight']): ?>
      <div>Weight: <?= htmlspecialchars($fm['weight']) ?></div>
    <?php endif; ?>

    <?php if($fm['education']): ?>
      <div>Education: <?= htmlspecialchars($fm['education']) ?></div>
    <?php endif; ?>

    <?php if($fm['occupation']): ?>
      <div>Occupation: <?= htmlspecialchars($fm['occupation']) ?></div>
    <?php endif; ?>

    <?php if($fm['income']): ?>
      <div>Income: <?= htmlspecialchars($fm['income']) ?></div>
    <?php endif; ?>

    <?php if($fm['caste']): ?>
      <div>Caste: <?= htmlspecialchars($fm['caste']) ?></div>
    <?php endif; ?>

    <?php if($fm['kuldevi']): ?>
      <div>Kuldevi: <?= htmlspecialchars($fm['kuldevi']) ?></div>
    <?php endif; ?>

  </div>

  <!-- READ MORE BUTTON -->
  <button type="button"
          class="text-orange-600 text-xs font-semibold mt-1 readMoreBtn">
    Read more
  </button>

</div>

          </div>
          <?php endwhile; ?>

        </div>
        <?php else: ?>
        <p class="text-gray-500 text-sm">No family members added.</p>
        <?php endif; ?>

      </div>

      <!-- MARRIAGE PROFILE -->
      <?php if($marriage_profile): ?>

      <a href="view_marriage_profile.php?id=<?= $marriage_profile['id'] ?>"
        class="block bg-orange-50 hover:bg-orange-100 transition border border-orange-200 rounded-xl p-4 shadow-sm">

        <h3 class="text-lg font-bold text-orange-800 mb-3 flex items-center gap-2">
          <i class="fa fa-ring"></i> Marriage Profile
        </h3>

        <div class="flex items-center gap-4">

          <img src="<?= !empty($marriage_profile['photo']) 
                      ? 'uploads/photo/'.$marriage_profile['photo'] 
                      : 'https://via.placeholder.com/80?text=Photo' ?>"
            class="w-16 h-16 rounded-full border-4 border-orange-400 object-cover shadow" />

          <div>
            <div class="font-bold text-orange-700 text-lg">
              <?= htmlspecialchars($marriage_profile['full_name']); ?>
            </div>

            <?php if(!empty($marriage_profile['dob'])): 
              $age = date_diff(date_create($marriage_profile['dob']), date_create('today'))->y; ?>

            <div class="text-sm text-gray-600">
              Age:
              <?= $age ?> Years
            </div>
            <?php endif; ?>

            <div class="text-sm text-gray-600">
              <?= htmlspecialchars($marriage_profile['city']) ?>,
              <?= htmlspecialchars($marriage_profile['caste']) ?>
            </div>

            <p class="text-xs text-orange-600 mt-1">Click to view full marriage profile →</p>
          </div>

        </div>

      </a>

      <?php endif; ?>

    </div>

  </div>

  <!-- Scrollbar Design -->
  <style>
    .custom-scroll::-webkit-scrollbar {
      width: 6px;
    }

    .custom-scroll::-webkit-scrollbar-thumb {
      background: #fb923c;
      border-radius: 10px;
    }

    .custom-scroll::-webkit-scrollbar-track {
      background: #fdecd5;
    }
  </style>



  <!-- 📝 Posts -->
  <section id="postContainer" class="mt-6"></section>
</main>

<!-- Premium Compact Profile Modal -->
<div id="profileModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">

  <div class="bg-white w-[92%] max-w-md rounded-xl shadow-2xl overflow-hidden border border-orange-200">

    <!-- HEADER (fixed) -->
    <div class="bg-gradient-to-r from-orange-600 to-orange-500 p-5 text-white relative">

      <button id="closeModal" class="absolute top-3 right-4 text-white/90 text-2xl hover:text-white font-bold">
        &times;
      </button>

      <div class="flex items-center gap-4">

        <!-- Photo -->
        <?php if(!empty($user['profile_photo']) && file_exists("uploads/photo/".$user['profile_photo'])): ?>
        <img src="uploads/photo/<?= $user['profile_photo']; ?>"
          class="w-16 h-16 rounded-lg object-cover shadow-md border-2 border-white">
        <?php else: ?>
        <div
          class="w-16 h-16 rounded-lg bg-white/30 flex items-center justify-center text-white text-3xl font-bold shadow">
          <?= strtoupper($user['name'][0] ?? 'U'); ?>
        </div>
        <?php endif; ?>

        <!-- Name -->
        <div>
          <h2 class="text-xl font-bold">
            <?= htmlspecialchars($user['name']); ?>
          </h2>
          <p class="text-sm text-white/80">User Profile Details</p>
        </div>

      </div>
    </div>

    <!-- CONTENT (scrollable) -->
    <div class="p-5 max-h-[65vh] overflow-y-auto space-y-4">

      <!-- FIELD COMPONENT -->
      <?php
      function field($icon, $title, $value){
          echo "
            <div class='flex items-start gap-3 bg-orange-50 border border-orange-200 p-3 rounded-lg shadow-sm'>
              <i class='fa-solid $icon text-orange-600 text-lg mt-1'></i>
              <p>
                <b>$title:</b><br>
                ".htmlspecialchars($value)."
              </p>
            </div>
          ";
      }
      ?>

      <?php
        field('fa-cake-candles', 'Date of Birth', $user['dob'] ?? 'Not available');
        field('fa-location-dot', 'Address', $user['address'] ?? 'Not available');
        field('fa-city', 'City', $user['city'] ?? 'Not available');
        field('fa-ring', 'Marital Status', $user['maritial_status'] ?? 'Not specified');
        field('fa-heart', 'Hobbies', $user['hobbi'] ?? 'Not listed');
        field('fa-user-pen', 'About', $user['about'] ?? 'No bio added');
        field('fa-graduation-cap', 'Education', $user['education'] ?? 'Not updated');
        field('fa-briefcase', 'Occupation', $user['occupation'] ?? 'Not updated');
        field('fa-people-group', 'Community / Caste', $user['cast'] ?? 'Not specified');
      ?>

    </div>

  </div>
</div>

<!-- Modal (Hidden by default) marriage_profile view-->
<div id="imageModal" class="modal" onclick="closeImageModal()">
  <span class="close">&times;</span>
  <img class="modal-content" id="modalImage">
</div>
<style>
  /* Modal background */
  .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    padding-top: 60px;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.8);
    text-align: center;
  }

  /* Image inside modal */
  .modal-content {
    margin: auto;
    display: block;
    max-width: 90%;
    max-height: 80vh;
    border-radius: 10px;
  }

  /* Close button */
  .close {
    position: absolute;
    top: 20px;
    right: 35px;
    color: white;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
  }

  .close:hover {
    color: orange;
  }
</style>
<!-- family member js  -->
<script>
document.addEventListener("click", function(e){
  if(e.target.classList.contains("readMoreBtn")){
    const btn = e.target;
    const more = btn.previousElementSibling;

    if(more.classList.contains("hidden")){
      more.classList.remove("hidden");
      btn.innerText = "Read less";
    } else {
      more.classList.add("hidden");
      btn.innerText = "Read more";
    }
  }
});
</script>


<script>
  function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').style.display = "block";
  }

  function closeImageModal() {
    document.getElementById('imageModal').style.display = "none";
  }
</script>


<script>
  // Modal JS
  document.querySelectorAll("[data-modal-target]").forEach(button => {
    button.addEventListener("click", () => {
      const modal = document.getElementById(button.getAttribute("data-modal-target"));
      modal.classList.remove("hidden");
      modal.classList.add("flex");
    });
  });
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
  const user_id = <? php echo $user_id; ?>;

  // ✅ Fetch only this user's posts (like old style)
  async function fetchAll() {
    const res = await fetch(`like_comment_action.php?action=fetch_all&user_id=${user_id}`);
    const posts = await res.json();
    const container = document.getElementById("postContainer");
    container.innerHTML = '';

    posts.forEach(p => {
      const likedClass = p.user_liked ? 'fa-solid text-red-500' : 'fa-regular text-gray-400';
      const postHTML = `
      <div class="bg-white rounded-xl shadow-lg border border-orange-200 px-6 py-5 mt-5" id="post-${p.id}">
        <div class="flex items-center gap-3">
          <a href="user_profile.php?id=${p.user_id}" class="flex items-center gap-3 hover:opacity-90 transition">
            <img src="uploads/photo/${p.profile_photo}" class="w-10 h-10 rounded-full border-2 border-orange-300">
            <div>
              <div class="font-bold text-orange-700 hover:underline">${p.name}</div>
              <div class="text-xs text-gray-500">${p.date}</div>
            </div>
          </a>
        </div>

        <div class="mt-3 text-gray-800 text-lg">${p.status}</div>
          <div class="mt-3 text-gray-800 text-lg break-all">
  <a href="${p.link}" 
     class="text-blue-700 break-all" 
     target="_blank">
     ${p.link}
  </a>
</div>

        ${p.media.map(m => m.endsWith('.jpg') || m.endsWith('.png') || m.endsWith('.jpeg') || m.endsWith('.gif') ?
        `<img src="uploads/posts/${m}" class="rounded-xl mt-3 max-h-[500px] mx-auto">` :
        `<video src="uploads/posts/${m}" class="rounded-xl mt-3 max-h-[500px] mx-auto" controls></video>`).join('')}

        <div class="flex gap-6 mt-3 text-gray-700 text-base">
          <button class="like-btn flex items-center gap-1" data-id="${p.id}">
            <i class="${likedClass} fa-heart text-lg"></i>
            <span class="like-count">${p.likes}</span>
          </button>
          <button class="comment-toggle hover:text-orange-600 flex items-center gap-1" data-id="${p.id}">
            <i class="fa-regular fa-comment-dots"></i> 
            <span class="comment-count">${p.comments.length}</span>
          </button>
        </div>

        <div id="comments-${p.id}" class="comment-section hidden mt-4 bg-orange-50/40 rounded-xl border border-orange-200 p-4">

    <!-- Add Comment -->
    <form class="comment-form flex items-center gap-3 mb-3" data-id="${p.id}">
        <img src="uploads/photo/<?=$loged_profile_photo?>" class="w-9 h-9 rounded-full border border-orange-300">
        <input 
            type="text" 
            name="comment"
            class="flex-1 bg-white border border-orange-200 rounded-full px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-orange-400"
            placeholder="Write a comment..."
            required
        >
        <button class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-full text-sm shadow">
            Post
        </button>
    </form>

    <!-- Comments List -->
    <div class="comment-list max-h-64 overflow-y-auto space-y-3 pr-1">

        ${p.comments.map(c => `
            <div class="flex gap-3 items-start border-b border-orange-100 pb-3">
                
                <img src="uploads/photo/${c.profile_photo}" 
                     class="w-9 h-9 rounded-full border border-orange-300">

                <div class="bg-white px-4 py-2 rounded-xl shadow-sm w-full">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-orange-700 text-sm">${c.name}</span>
                        <span class="text-[10px] text-gray-400">${c.date}</span>
                    </div>
                    <div class="text-gray-700 text-sm mt-1 leading-tight">
                        ${c.comment}
                    </div>
                </div>

            </div>
        `).join('')}

    </div>

</div>

      </div>`;
      container.insertAdjacentHTML("beforeend", postHTML);
    });
  }

  // ❤️ Like toggle
  document.addEventListener('click', async e => {
    const btn = e.target.closest('.like-btn');
    if (!btn) return;
    const id = btn.dataset.id;
    await fetch('like_comment_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=like&id=${id}`
    });
    fetchAll();
  });

  // 💬 Toggle comment section
  document.addEventListener('click', e => {
    const btn = e.target.closest('.comment-toggle');
    if (!btn) return;
    const id = btn.dataset.id;
    document.querySelector(`#comments-${id}`).classList.toggle('hidden');
  });

  // ✏️ Post comment
  document.addEventListener('submit', async e => {
    if (!e.target.classList.contains('comment-form')) return;

    e.preventDefault();

    const form = e.target;
    const id = form.dataset.id;

    // CHANGED: textarea ❌ → input[type=text] ✔
    const input = form.querySelector("input[name='comment']");
    const text = input.value.trim();

    if (text === "") return;

    await fetch('like_comment_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=comment&id=${id}&comment=${encodeURIComponent(text)}`
    });

    input.value = ""; // clear input after posting
    fetchAll();        // reload posts
  });


  // 🧡 Modal
  document.getElementById('moreBtn').onclick = () => document.getElementById('profileModal').classList.remove('hidden');
  document.getElementById('closeModal').onclick = () => document.getElementById('profileModal').classList.add('hidden');

  fetchAll();
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