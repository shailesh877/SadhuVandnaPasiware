<?php
include("header.php");
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
$success_text = '';
if($success_msg == 1){
    $success_text = 'Your post has been created successfully!';
}

include("connection.php");

if(!isset($_SESSION['sadhu_user_id'])){
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['sadhu_user_id'];
$user_q = $con->query("SELECT * FROM tbl_members WHERE email='$user_id' LIMIT 1");
$user = $user_q->fetch_assoc();

$story_q = $con->query("
  SELECT s.*, m.name, m.profile_photo 
  FROM tbl_stories s 
  JOIN tbl_members m ON s.user_id = m.id 
  WHERE s.date > (NOW() - INTERVAL 1 DAY)
  ORDER BY s.date DESC
");
?>
<title>Sadhu Vandana | Home</title>
<style>
#pageLoader {
  transition: opacity 0.5s ease, visibility 0.5s ease;
}
#pageLoader.hide {
  opacity: 0;
  visibility: hidden;
}
</style>

<script>
window.addEventListener("load", function () {
  setTimeout(function () {
    document.getElementById("pageLoader").classList.add("hide");
  }, 300); // ✅ 3.5 seconds
});
</script>

<!-- Main Content -->
<main class="flex-1 px-2 md:px-10 py-15  md:ml-20 mb-13 md:mb-0 max-w-7xl overflow-hidden">
 <!-- loader section  -->
  <!-- PAGE LOADER -->
<div id="pageLoader" class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-white">

  <!-- SPINNER -->
  <div class="w-14 h-14 border-4 border-orange-300 border-t-orange-600 rounded-full animate-spin mb-4"></div>

  <!-- TEXT -->
  <div class="text-orange-600 font-bold text-lg tracking-wide">
    Loading...
  </div>

</div>


  <section class="flex flex-col gap-4 flex-1">
    <?php if($success_text){ ?>
    <div class="bg-green-100 text-green-700 px-4 py-1 rounded-lg border border-green-200 text-center">
      <?= $success_text ?>
    </div>
    <?php } ?>
    <!-- Status Input Card -->
    <div class="bg-white rounded-xl shadow-lg border border-orange-200 px-6 pt-6 pb-4">
      <div class="flex gap-4 items-center  font-bold">
        <a href="profile"><?php if($user['profile_photo']){?>
        <img src="uploads/photo/<?php echo $user['profile_photo'];?>" class="w-11 h-11 rounded-full "
          alt="Youabout"/>
          <?php } else{?>
            <span class="w-11 h-11 rounded-full border-2 border-orange-500 p-2 text-orange-800 bg-orange-200">
           <?php echo $first_letters;?>
           </span>
          <?php }?>
            </a>
        <input type="text" id="statusInput" placeholder="What's on your mind?"
          class="flex-1 py-3 px-5 rounded-xl bg-orange-50 border-none text-lg focus:outline-none cursor-pointer"
          readonly />
      </div>
      <div class="flex gap-4 mt-4">
        <button id="photoBtn"
          class="flex items-center gap-2 px-4 py-2 bg-orange-500 text-white font-semibold rounded-xl shadow hover:bg-orange-700 transition">
          <i class="fa fa-image"></i> Photo
        </button>
        <button id="videoBtn"
          class="flex items-center gap-2 px-4 py-2 bg-orange-100 text-orange-700 font-bold rounded-xl border-2 border-orange-400 shadow hover:bg-orange-50 transition">
          <i class="fa fa-video"></i> Video
        </button>
        <button id="postBtn"
          class="ml-auto px-4 py-2 bg-orange-600 text-white font-bold rounded-xl shadow hover:bg-orange-700 transition">
          Post
        </button>
      </div>
    </div>

    <!-- Modal -->
    <!-- Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 hidden">
      <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl p-6 relative">
        <button id="closeModal" class="absolute top-4 right-4 text-orange-500 hover:text-orange-700">
          <i class="fa fa-times text-xl"></i>
        </button>
        <h2 class="text-xl font-bold text-orange-600 mb-4">Create Post</h2>
        <form action="post.php" method="post" enctype="multipart/form-data" class="flex flex-col gap-4">
          <textarea name="status" placeholder="What's on your mind?" required
            class="w-full border border-orange-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 ring-orange-200"></textarea>
         <input type="text" name="link" placeholder="Add Link (optional)"
            class="w-full border border-orange-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 ring-orange-200">
          <!-- Hidden file input for multiple files -->
          <input type="file" id="mediaInput" name="media[]" accept="image/*,video/*" multiple class="hidden">

          <!-- Preview container -->
          <div id="mediaPreview" class="grid grid-cols-3 gap-4"></div>

          <div class="flex gap-4">
            <button type="button" id="modalPhotoBtn"
              class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-orange-500 text-white font-semibold rounded-xl shadow hover:bg-orange-700 transition">
              <i class="fa fa-image"></i> Photo
            </button>
            <button type="button" id="modalVideoBtn"
              class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-orange-100 text-orange-700 font-bold rounded-xl border-2 border-orange-400 shadow hover:bg-orange-50 transition">
              <i class="fa fa-video"></i> Video
            </button>
          </div>

          <button type="submit"
            class="bg-orange-500 text-white font-bold py-2 rounded-xl shadow hover:bg-orange-700 transition flex items-center justify-center gap-2">
            <i class="fa fa-paper-plane"></i> Post
          </button>
        </form>
      </div>
    </div>



    <!-- Stories/Highlights Bar -->

    <?php 
        include("stories.php");
        ?>

    <!-- Another Post -->
    <?php
  include("all_posts.php");
    ?>
    <!-- post section end  -->
  
</main>
<!-- Main Content end -->
</div>








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

<!-- modal script for posts  -->
<script>
  const statusInput = document.getElementById('statusInput');
  const statusModal = document.getElementById('statusModal');
  const closeModal = document.getElementById('closeModal');
  const photoBtn = document.getElementById('photoBtn');
  const videoBtn = document.getElementById('videoBtn');
  const postBtn = document.getElementById('postBtn');
  const modalPhotoBtn = document.getElementById('modalPhotoBtn');
  const modalVideoBtn = document.getElementById('modalVideoBtn');
  const mediaInput = document.getElementById('mediaInput');
  const mediaPreview = document.getElementById('mediaPreview');

  // Open modal
  [statusInput, photoBtn, videoBtn, postBtn].forEach(el => {
    el.addEventListener('click', () => statusModal.classList.remove('hidden'));
  });

  // Close modal
  closeModal.addEventListener('click', () => statusModal.classList.add('hidden'));
  statusModal.addEventListener('click', (e) => {
    if (e.target === statusModal) statusModal.classList.add('hidden');
  });

  // Trigger hidden file input
  modalPhotoBtn.addEventListener('click', () => {
    mediaInput.accept = 'image/*';
    mediaInput.click();
  });
  modalVideoBtn.addEventListener('click', () => {
    mediaInput.accept = 'video/*';
    mediaInput.click();
  });

  // File preview
  mediaInput.addEventListener('change', (e) => {
    mediaPreview.innerHTML = ''; // Clear previous previews
    const files = Array.from(e.target.files);

    files.forEach(file => {
      const url = URL.createObjectURL(file);
      const container = document.createElement('div');
      container.className = "relative w-full";

      if (file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = url;
        img.className = "w-full h-32 object-cover rounded-xl shadow";
        container.appendChild(img);
      } else if (file.type.startsWith('video/')) {
        const video = document.createElement('video');
        video.src = url;
        video.controls = true;
        video.className = "w-full h-32 object-cover rounded-xl shadow";
        container.appendChild(video);
      }

      mediaPreview.appendChild(container);
    });
  });
</script>

 
 <script>
    function googleTranslateElementInit() {
      new google.translate.TranslateElement(
        { pageLanguage: "en", includedLanguages: "en,hi,gu" },
        "google_translate_element"
      );
  }
</script>

</body>

</html>