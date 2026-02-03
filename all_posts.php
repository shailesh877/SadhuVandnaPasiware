<?php

// include("connection.php");

$logged_email = $_SESSION['sadhu_user_id'] ?? '';
$logged_user = $con->query("SELECT id,email FROM tbl_members WHERE email='$logged_email'")->fetch_assoc();
$logged_id = $logged_user['id'] ?? 0;
?>



  <!-- 📝 All Posts -->
  <section id="postContainer" class=""></section>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
async function fetchAll() {
  const res = await fetch('like_comment_action.php?action=fetch_all');
  const posts = await res.json();
  const container = document.getElementById("postContainer");
  container.innerHTML = '';

  posts.forEach(p => {
    // ✅ Heart class: solid red if liked, regular gray if not
    const likedClass = p.user_liked ? 'fa-solid text-red-500' : 'fa-regular text-gray-400';

    const postHTML = `
      <div class="bg-white max-w-6xl flex-1 w-full mx-auto rounded-xl shadow-lg border border-orange-200 px-6 py-5 mt-5" id="post-${p.id}">
        <div class="flex items-center gap-4">
          <a href="user_profile?id=${p.user_id}" class="flex items-center gap-3 hover:opacity-90 transition">
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


        ${p.media.map(m => m.match(/\.(jpg|jpeg|png|gif)$/i) ? 
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

  <button class="share-btn hover:text-orange-600 flex items-center gap-1" data-id="${p.id}">
    <i class="fa-solid fa-share"></i>
    Share
  </button>
</div>


        <div id="comments-${p.id}" class="comment-section hidden mt-4 bg-orange-50/40 rounded-xl border border-orange-200 p-4">

    <!-- Add Comment -->
    <form class="comment-form flex items-center gap-3 mb-3" data-id="${p.id}">
        <img src="uploads/photo/${p.profile_photo}" class="w-9 h-9 rounded-full border border-orange-300">
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

      </div>
    `;
    container.insertAdjacentHTML("beforeend", postHTML);
  });
}

// ❤️ Like toggle
document.addEventListener('click', async e => {
  const btn = e.target.closest('.like-btn');
  if(!btn) return;
  const id = btn.dataset.id;
  await fetch('like_comment_action.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=like&id=${id}`
  });
  fetchAll();
});

// 💬 Toggle comment section
document.addEventListener('click', e => {
  const btn = e.target.closest('.comment-toggle');
  if(!btn) return;
  const id = btn.dataset.id;
  document.querySelector(`#comments-${id}`).classList.toggle('hidden');
});

// ✏️ Post comment
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


fetchAll();


document.addEventListener('click', async e => {
  const btn = e.target.closest('.share-btn');
  if(!btn) return;

  const id = btn.dataset.id;

  const shareUrl = `${location.origin}${location.pathname}?post=${id}`;

  if (navigator.share) {
    navigator.share({
      title: "View this post",
      url: shareUrl
    }).catch(console.log);
  } else {
    navigator.clipboard.writeText(shareUrl);
    alert("Link copied!");
  }
});


// scroll to shared post
const urlParams = new URLSearchParams(window.location.search);
if(urlParams.get('post')){
  const id = urlParams.get('post');
  setTimeout(()=>{
    const post = document.getElementById('post-'+id);
    if(post){
      post.scrollIntoView({behavior:'smooth', block:'start'});
    }
  },500);
}

</script>
