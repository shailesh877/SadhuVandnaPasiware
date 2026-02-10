<?php
include("header.php");
include("connection.php");

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if (!$user_email) {
    header("Location: login.php");
    exit;
}

// Fetch user info
$user = $con->query("SELECT * FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
$user_id = $user['id'];

// Fetch user's posts with comments and likes count
$posts_query = $con->query("SELECT * FROM tbl_posts WHERE user_id='$user_id' ORDER BY created_at DESC");
$posts = [];
while($post = $posts_query->fetch_assoc()) {
    $post_id = $post['id'];

    // Fetch comments
    $comments_query = $con->query("SELECT c.*, m.name, m.profile_photo FROM tbl_comments c 
                                   LEFT JOIN tbl_members m ON c.user_id = m.id
                                   WHERE c.post_id='$post_id' ORDER BY c.date ASC");
    $comments = $comments_query->fetch_all(MYSQLI_ASSOC);
    $post['comments_data'] = $comments;

    // Fetch likes count from tbl_likes
    $likes_result = $con->query("SELECT COUNT(*) as total_likes FROM tbl_likes WHERE post_id='$post_id'");
    $likes = $likes_result->fetch_assoc();
    $post['likes'] = $likes['total_likes'] ?? 0;

    $posts[] = $post;
}

?>

<main class="flex-1 px-2 md:px-10 py-15 bg-white md:ml-20 mb-13 md:mb-0 max-w-8xl overflow-hidden">

<!-- Cover Photo -->
<div class="relative w-full h-80 rounded-2xl overflow-hidden shadow-lg mb-[-80px]">
    <?php if(!empty($user['cover_photo']) && file_exists("uploads/photo/".$user['cover_photo'])): ?>
        <img src="uploads/photo/<?php echo $user['cover_photo']; ?>" class="object-cover w-full h-full" onclick="openImageModal(this.src)"/>
    <?php else: ?>
        <div class="w-full h-full bg-gray-100 flex items-center justify-center text-gray-400 text-2xl">Cover Photo</div>
    <?php endif; ?>

    <!-- Profile Photo -->
    <div class="absolute bottom-7 left-1/2 -translate-x-1/2 z-2">
        <div class="relative">
            <?php if(!empty($user['profile_photo']) && file_exists("uploads/photo/".$user['profile_photo'])): ?>
                <img src="uploads/photo/<?php echo $user['profile_photo']; ?>" class="w-24 h-24 rounded-full border-4 border-orange-300 shadow-lg object-cover ring-4 ring-white" onclick="openImageModal(this.src)"/>
            <?php else: ?>
                <div class="w-24 h-24 flex items-center justify-center rounded-full border-4 border-orange-300 shadow-lg ring-4 ring-white bg-orange-200 text-white font-bold text-2xl">
                    <?php echo strtoupper($user['name'][0] ?? 'U'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Info Block -->
<div class="w-full bg-white/90 backdrop-blur shadow-xl rounded-2xl border border-orange-100 p-6 pt-16 flex flex-col items-center -mt-10 mb-3">
    <div class="font-extrabold text-2xl text-orange-700 mb-3"><?php echo htmlspecialchars($user['name']); ?></div>
    <div class="flex flex-wrap gap-3 text-md text-gray-500 mb-4 border-b border-orange-100 pb-3 justify-center">
        <span><i class="fa-solid fa-location-dot mr-1"></i> <?php echo htmlspecialchars($user['city'] ?? ''); ?></span>
        <span><i class="fa-solid fa-home mr-1"></i> From <?php echo htmlspecialchars($user['state'] ?? ''); ?></span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 w-full">
        <a href="edit_profile.php" class="text-orange-700 border border-orange-200 px-4 py-1 rounded-lg hover:bg-orange-50 text-sm w-full text-center mb-2 font-medium">
            <i class="fa-solid fa-pen mr-2"></i>Edit Profile
        </a>
        <a href="family" class="text-orange-700 border border-orange-200 px-4 py-1 rounded-lg hover:bg-orange-50 text-sm w-full text-center mb-2 font-medium">
            <i class="fa-solid fa-square-plus"></i> Family
        </a>
    </div>
</div>



    
<!-- Marriage Bureau Section -->
<div class="bg-gradient-to-br from-orange-50 to-white rounded-3xl shadow-xl border border-orange-200 p-6 mb-6">

<?php
// Check if marriage profile exists
$marriage_q = $con->prepare("SELECT * FROM tbl_marriage_profiles WHERE user_id = ? LIMIT 1");
$marriage_q->bind_param("i", $user_id);
$marriage_q->execute();
$marriage_result = $marriage_q->get_result();
$marriage = $marriage_result->fetch_assoc();

// Calculate Age
$age = '';
if($marriage && !empty($marriage['dob'])) {
    $dob = new DateTime($marriage['dob']);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
}
?>

<h3 class="font-bold text-2xl text-orange-700 mb-5 flex items-center gap-2">
    <i class="fa-solid fa-heart-circle-bolt text-orange-600 text-3xl"></i>
    Marriage Bureau Profile
</h3>

<?php if(!$marriage): ?>

    <div class="text-gray-600 mb-3 text-lg">You have not created a marriage profile yet.</div>
    <a href="add_marriage_profile" 
       class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded-xl shadow-md transition">
        + Create Marriage Profile
    </a>

<?php else: ?>

<!-- Main Card -->
<div class="flex flex-col md:flex-row gap-6">

    <!-- Left: Profile Image -->
    <div class="flex flex-col items-center bg-white rounded-2xl shadow-lg border border-orange-100 p-5 w-full md:w-1/3">

        <?php if(!empty($marriage['photo']) && file_exists("uploads/photo/".$marriage['photo'])): ?>
            <img src="uploads/photo/<?= $marriage['photo']; ?>"
                 class="w-40 h-40 rounded-2xl object-cover shadow-md border border-orange-200">
        <?php else: ?>
            <div class="w-40 h-40 rounded-2xl bg-orange-200 flex items-center justify-center text-white text-6xl shadow">
                <?= strtoupper($user['name'][0] ?? 'U'); ?>
            </div>
        <?php endif; ?>

        <div class="mt-4 text-center">
            <div class="text-xl font-bold text-orange-700"><?= htmlspecialchars($marriage['full_name']); ?></div>
            <div class="text-gray-500 text-sm"><?= $age ? $age.' Years' : 'Age Not Provided'; ?></div>
        </div>

        <a href="add_marriage_profile?id=<?= $marriage['id']; ?>"
           class="mt-4 w-full bg-orange-600 hover:bg-orange-700 text-white py-2 rounded-xl shadow transition text-center font-medium">
            ✎ Edit Profile
        </a>

    </div>

    <!-- Right: Details -->
    <div class="flex-1 bg-white rounded-2xl shadow-lg border border-orange-100 p-6">

        <h4 class="text-lg font-bold text-orange-700 mb-3">Basic Information</h4>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">

            <div class="flex items-center gap-2">
                <i class="fa-solid fa-venus-mars text-orange-500"></i>
                <span><strong>Gender:</strong> <?= htmlspecialchars($marriage['gender']); ?></span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fa-solid fa-graduation-cap text-orange-500"></i>
                <span><strong>Education:</strong> <?= htmlspecialchars($marriage['education']); ?></span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fa-solid fa-map-pin text-orange-500"></i>
                <span><strong>City:</strong> <?= htmlspecialchars($marriage['city']); ?></span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fa-solid fa-ring text-orange-500"></i>
                <span><strong>Marital Status:</strong> <?= htmlspecialchars($marriage['status']); ?></span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fa-solid fa-briefcase text-orange-500"></i>
                <span><strong>Occupation:</strong> <?= htmlspecialchars($marriage['occupation']); ?></span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fa-solid fa-users text-orange-500"></i>
                <span><strong>Community:</strong> <?= htmlspecialchars($marriage['caste']); ?></span>
            </div>

        </div>

        <div class="mt-5 gap-2 flex flex-wrap">
        
            <a href="view_request" 
               class="bg-orange-100 text-orange-700 hover:bg-orange-200 px-5 py-2 rounded-xl shadow transition font-medium">
                Requests
            </a>
            <a href="send_request" 
               class="bg-orange-100 text-orange-700 hover:bg-orange-200 px-5 py-2 rounded-xl shadow transition font-medium">
                Send
            </a>
            <a href="connected" 
               class="bg-orange-100 text-orange-700 hover:bg-orange-200 px-5 py-2 rounded-xl shadow transition font-medium">
                Connected
            </a>

    </div>

</div>

<?php endif; ?>

</div>
        </div>

<!-- Posts -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-4 mb-10">
    <?php if($posts): ?>
        <?php foreach($posts as $post): ?>
        <div class="bg-white/80 backdrop-blur rounded-2xl shadow-xl border border-orange-200 px-6 py-6">
            <!-- POST HEADER WITH DELETE -->
<div class="flex justify-between items-center mb-2">
  <div class="text-sm text-gray-500">
    <?= date("d M Y, h:i A", strtotime($post['created_at'])) ?>
  </div>

  <a href="delete_post.php?id=<?= $post['id']; ?>"
     onclick="return confirm('Are you sure you want to delete this post?');"
     class="text-red-600 hover:text-red-800 text-sm font-semibold flex items-center gap-1">
     <i class="fa-solid fa-trash"></i> Delete
  </a>
</div>

            <div class="mt-4 text-lg text-gray-800 font-medium"><?php echo htmlspecialchars($post['status']); ?></div>
            <div class="mt-4 text-lg text-gray-800 font-medium break-all">
  <a href="<?php echo htmlspecialchars($post['link']); ?>" 
     class="text-blue-700 break-all" 
     target="_blank" 
     rel="noopener noreferrer">
     <?php echo htmlspecialchars($post['link']); ?>
  </a>
</div>

           
            <?php 
            $media_path = "uploads/posts/".$post['media'];
            if(!empty($post['media']) && file_exists($media_path)): 
                $ext = pathinfo($media_path, PATHINFO_EXTENSION);
                if(in_array(strtolower($ext), ['mp4','webm','ogg'])): ?>
                    <video controls class="mt-4 rounded-xl shadow border border-orange-100 w-full max-h-72">
                        <source src="<?php echo $media_path; ?>" type="video/<?php echo $ext; ?>">
                        Your browser does not support the video tag.
                    </video>
                <?php else: ?>
                    <img src="<?php echo $media_path; ?>" class="mt-4 rounded-xl shadow border border-orange-100 object-cover w-full max-h-72" />
                <?php endif; 
            endif; ?>

          <!-- Likes & Comments Buttons -->
<div class="flex gap-7 mt-4 text-gray-700 text-lg">
    <button class="hover:text-orange-600 flex gap-2 items-center">
        <i class="fa fa-heart"></i> <?php echo $post['likes']; ?>
    </button>
    <button class="hover:text-orange-600 flex gap-2 items-center" data-modal-target="commentsModal<?php echo $post['id']; ?>">
        <i class="fa fa-comment-alt"></i> <?php echo count($post['comments_data']); ?>
    </button>
    
</div>


            <!-- Comments Modal -->
            <div id="commentsModal<?php echo $post['id']; ?>" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
                <div class="bg-white rounded-2xl w-11/12 md:w-1/2 max-h-[80vh] overflow-y-auto p-6 relative">
                    <button onclick="document.getElementById('commentsModal<?php echo $post['id']; ?>').classList.add('hidden')" 
                            class="absolute top-3 right-3 text-gray-500 hover:text-orange-500 text-xl">&times;</button>
                    <h3 class="text-xl font-bold text-orange-700 mb-4">Comments</h3>

                    <!-- Existing Comments -->
                    <?php if(!empty($post['comments_data'])): ?>
                        <?php foreach($post['comments_data'] as $comment): ?>
                            <div class="flex items-start gap-3 mb-3">
                                <?php if(!empty($comment['profile_photo']) && file_exists("uploads/photo".$comment['profile_photo'])): ?>
                                    <img src="uploads/photo<?php echo $comment['profile_photo']; ?>" class="w-10 h-10 rounded-full object-cover border border-orange-200" />
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-orange-200 text-white flex items-center justify-center font-bold"><?php echo strtoupper($comment['name'][0] ?? 'U'); ?></div>
                                <?php endif; ?>
                                <div>
                                    <div class="text-sm font-bold text-orange-700"><?php echo htmlspecialchars($comment['name']); ?></div>
                                    <div class="text-gray-600 text-sm"><?php echo htmlspecialchars($comment['comment']); ?></div>
                                    <div class="text-gray-400 text-xs"><?php echo date("d M Y h:i A", strtotime($comment['date'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-gray-500 text-center py-10">No comments yet.</div>
                    <?php endif; ?>

                    <!-- Add Comment Form -->
                    <form method="post" action="add_comment.php" class="mt-4">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <textarea name="comment" placeholder="Write a comment..." required class="w-full border border-orange-200 rounded-xl p-2 mb-2"></textarea>
                        <button type="submit" class="bg-orange-600 text-white px-4 py-1 rounded-lg hover:bg-orange-700">Add Comment</button>
                    </form>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full text-center text-gray-500 py-10">No posts yet.</div>
    <?php endif; ?>
</div>

</main>

<!-- Modal (Hidden by default) profile view-->
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
  background-color: rgba(0,0,0,0.8); 
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
