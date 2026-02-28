<?php
include("header.php");
include("connection.php");

$user_mobile = $_SESSION['sadhu_user_id'] ?? '';
if (!$user_mobile) {
    header("Location: login.php");
    exit;
}

// Fetch user info using MOBILE
$user = $con->query("SELECT * FROM tbl_members WHERE mobile='$user_mobile'")->fetch_assoc();
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
    <div class="font-extrabold text-2xl text-orange-700 mb-1"><?php echo htmlspecialchars($user['name'] ?? ''); ?></div>
    <div class="flex flex-wrap gap-3 text-sm text-gray-400 mb-4 pb-3 justify-center">
        <span><i class="fa-solid fa-location-dot mr-1"></i> <?php echo htmlspecialchars($user['city'] ?? ''); ?>, <?php echo htmlspecialchars($user['state'] ?? ''); ?></span>
    </div>

    <!-- Stats Section -->
    <div class="flex flex-wrap justify-center gap-4 md:gap-10 w-full py-4 border-y border-orange-50 mb-6">
        <div class="text-center">
            <?php
            $post_count = $con->query("SELECT COUNT(*) FROM tbl_posts WHERE user_id=$user_id")->fetch_row()[0];
            $followers_count = $con->query("SELECT COUNT(*) FROM tbl_followers WHERE following_id=$user_id")->fetch_row()[0];
            $following_count = $con->query("SELECT COUNT(*) FROM tbl_followers WHERE follower_id=$user_id")->fetch_row()[0];
            $requested_count = $con->query("SELECT COUNT(*) FROM tbl_followers WHERE following_id=$user_id AND status='pending'")->fetch_row()[0];
            ?>
            <div class="font-bold text-xl text-orange-700"><?php echo $post_count; ?></div>
            <div class="text-[10px] md:text-xs text-gray-500 uppercase tracking-wider font-semibold">Posts</div>
        </div>
        <div class="text-center cursor-pointer hover:bg-orange-50 px-2 py-1 rounded-xl transition" onclick="openFollowsModal('fetch_followers', 'Followers')">
            <div class="font-bold text-xl text-orange-700"><?php echo $followers_count; ?></div>
            <div class="text-[10px] md:text-xs text-gray-500 uppercase tracking-wider font-semibold">Followers</div>
        </div>
        <div class="text-center cursor-pointer hover:bg-orange-50 px-2 py-1 rounded-xl transition" onclick="openFollowsModal('fetch_following', 'Following')">
            <div class="font-bold text-xl text-orange-700"><?php echo $following_count; ?></div>
            <div class="text-[10px] md:text-xs text-gray-500 uppercase tracking-wider font-semibold">Following</div>
        </div>
        <div class="text-center cursor-pointer hover:bg-orange-50 px-2 py-1 rounded-xl transition" onclick="openFollowsModal('fetch_requested', 'Requests')">
            <div class="font-bold text-xl text-orange-700 flex items-center justify-center gap-1">
                <?php echo $requested_count; ?>
                <?php if($requested_count > 0): ?>
                    <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                <?php endif; ?>
            </div>
            <div class="text-[10px] md:text-xs text-red-500 uppercase tracking-wider font-semibold">Requests</div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 w-full max-w-sm">
        <a href="edit_profile.php" class="bg-orange-600 text-white px-4 py-2 rounded-xl hover:bg-orange-700 text-sm text-center font-bold shadow-md transition">
            <i class="fa-solid fa-pen mr-1"></i> Edit
        </a>
        <a href="family" class="bg-orange-100 text-orange-700 px-4 py-2 rounded-xl hover:bg-orange-200 text-sm text-center font-bold shadow-sm transition border border-orange-200">
            <i class="fa-solid fa-users mr-1"></i> Family
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
            <div class="text-xl font-bold text-orange-700"><?= htmlspecialchars($marriage['full_name'] ?? ''); ?></div>
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
                <span><strong>Gender:</strong> <?= htmlspecialchars($marriage['gender'] ?? ''); ?></span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fa-solid fa-graduation-cap text-orange-500"></i>
                <span><strong>Education:</strong> <?= htmlspecialchars($marriage['education'] ?? ''); ?></span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fa-solid fa-map-pin text-orange-500"></i>
                <span><strong>City:</strong> <?= htmlspecialchars($marriage['city'] ?? ''); ?></span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fa-solid fa-ring text-orange-500"></i>
                <span><strong>Marital Status:</strong> <?= htmlspecialchars($marriage['status'] ?? ''); ?></span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fa-solid fa-briefcase text-orange-500"></i>
                <span><strong>Occupation:</strong> <?= htmlspecialchars($marriage['occupation'] ?? ''); ?></span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fa-solid fa-users text-orange-500"></i>
                <span><strong>Community:</strong> <?= htmlspecialchars($marriage['caste'] ?? ''); ?></span>
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

            <div class="mt-4 text-lg text-gray-800 font-medium"><?php echo htmlspecialchars($post['status'] ?? ''); ?></div>
            <div class="mt-4 text-lg text-gray-800 font-medium break-all">
  <a href="<?php echo htmlspecialchars($post['link']); ?>" 
     class="text-blue-700 break-all" 
     target="_blank" 
     rel="noopener noreferrer">
     <?php echo htmlspecialchars($post['link'] ?? ''); ?>
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
                                     <div class="text-sm font-bold text-orange-700"><?php echo htmlspecialchars($comment['name'] ?? ''); ?></div>
                                     <div class="text-gray-600 text-sm"><?php echo htmlspecialchars($comment['comment'] ?? ''); ?></div>
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

<!-- Follows Modal -->
<div id="followsModal" class="fixed inset-0 bg-black/50 z-[1000] hidden items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden animate-scale-in">
        <div class="p-4 border-b border-orange-100 flex justify-between items-center bg-orange-50">
            <h3 id="followsModalTitle" class="font-bold text-orange-700 text-lg capitalize">List</h3>
            <button onclick="closeFollowsModal()" class="text-gray-400 hover:text-orange-600 text-2xl">&times;</button>
        </div>
        <div id="followsList" class="max-h-[60vh] overflow-y-auto p-2 space-y-2 custom-scroll">
            <!-- Items loaded here -->
        </div>
    </div>
</div>

<script>
async function openFollowsModal(action, title) {
    const modal = document.getElementById('followsModal');
    const list = document.getElementById('followsList');
    const titleEl = document.getElementById('followsModalTitle');
    
    titleEl.innerText = title;
    list.innerHTML = `<div class="p-10 text-center"><i class="fa-solid fa-spinner fa-spin text-orange-500 text-2xl"></i></div>`;
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    const res = await fetch(`follow_action.php?action=${action}&user_id=<?php echo $user_id; ?>`);
    const data = await res.json();
    
    if(data.ok){
        if(data.list.length === 0){
            list.innerHTML = `<div class="p-10 text-center text-gray-400 italic">No ${title.toLowerCase()} yet.</div>`;
            return;
        }
        
        list.innerHTML = data.list.map(u => `
            <div class="flex items-center justify-between p-3 bg-white border border-gray-50 rounded-xl hover:shadow-sm transition">
                <a href="user_profile?id=${u.id}" class="flex items-center gap-3">
                    ${u.profile_photo ? 
                        `<img src="${u.profile_photo}" class="w-10 h-10 rounded-full object-cover border border-orange-200">` : 
                        `<div class="w-10 h-10 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold border border-orange-200">${u.initials}</div>`
                    }
                    <div>
                        <div class="font-bold text-gray-800 text-sm">${u.name}</div>
                        <div class="text-[10px] text-gray-400">${u.city || ''} ${u.follows_me ? '• <span class="text-green-600">Follows You</span>' : ''}</div>
                    </div>
                </a>
                <div class="flex flex-col items-end gap-1">
                    <button onclick="toggleFollowInModal(${u.id}, this, '${action}', '${title}')" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all ${u.i_follow ? 'bg-gray-100 text-gray-600' : 'bg-orange-500 text-white'} min-w-[90px]">
                        ${u.i_follow ? (u.my_status === 'accepted' ? 'Friends' : 'Requested') : (u.follows_me ? 'Accept Request' : 'Send Request')}
                    </button>
                    ${action === 'fetch_followers' ? `
                        <button onclick="removeFollowerInModal(${u.id}, '${action}', '${title}')" class="text-[9px] text-gray-400 hover:text-red-500 font-bold uppercase tracking-wider">
                            Remove
                        </button>
                    ` : ''}
                    ${action === 'fetch_requested' ? `
                        <button onclick="removeFollowerInModal(${u.id}, '${action}', '${title}')" class="text-[9px] text-gray-400 hover:text-red-500 font-bold uppercase tracking-wider">
                            Reject
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }
}

function closeFollowsModal() {
    const modal = document.getElementById('followsModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

async function toggleFollowInModal(id, btn, action, title) {
    const res = await fetch('follow_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=follow&user_id=${id}`
    });
    const data = await res.json();
    if(data.ok){
        openFollowsModal(action, title);
        if(window.location.pathname.includes('profile.php')) location.reload();
    }
}

async function removeFollowerInModal(id, action, title) {
    let msg = action === 'fetch_requested' ? 'Are you sure you want to reject this request?' : 'Are you sure you want to remove this follower? They will no longer follow you.';
    if(!confirm(msg)) return;
    
    const res = await fetch('follow_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=remove_follower&user_id=${id}`
    });
    const data = await res.json();
    if(data.ok){
        openFollowsModal(action, title);
        location.reload();
    }
}
</script>

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
