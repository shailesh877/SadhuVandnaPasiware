<?php

// include("connection.php");

$logged_mobile = $_SESSION['sadhu_user_id'] ?? '';
$logged_user = $con->query("SELECT id, name, profile_photo FROM tbl_members WHERE mobile='$logged_mobile'")->fetch_assoc();
$logged_id = $logged_user['id'] ?? 0;
$my_name = htmlspecialchars($logged_user['name'] ?? 'Me');
$my_photo = htmlspecialchars($logged_user['profile_photo'] ?? '');
?>
<script>
  const myName = "<?= $my_name ?>";
  const myPhoto = "<?= $my_photo ?>";
</script>



  <!-- 📝 All Posts -->
  <section id="postContainer" class=""></section>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
let offset = 0;
let renderedCount = 0;
const limit = 2;
let isLoading = false;
let hasMore = true;

async function fetchPosts(isInitial = false) {
  if (isLoading || !hasMore) return;
  isLoading = true;

  if (isInitial) {
    offset = 0;
    hasMore = true;
    renderedCount = 0; 
    document.getElementById("postContainer").innerHTML = '';
  }

  try {
    const res = await fetch(`like_comment_action.php?action=fetch_all&limit=${limit}&offset=${offset}`);
    const posts = await res.json();
    const container = document.getElementById("postContainer");

    if (posts.length < limit) {
      hasMore = false;
    }

    if (posts.length === 0 && isInitial) {
        container.innerHTML = '<div class="text-center text-gray-500 mt-10">No posts found.</div>';
        isLoading = false;
        return;
    }

    posts.forEach((p, index) => {
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
            <a href="${p.link}" class="text-blue-700 break-all" target="_blank">${p.link}</a>
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
              <input type="text" name="comment" class="flex-1 bg-white border border-orange-200 rounded-full px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-orange-400" placeholder="Write a comment..." required>
              <button class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-full text-sm shadow">Post</button>
            </form>

            <!-- Comments List -->
            <div class="comment-list max-h-64 overflow-y-auto space-y-3 pr-1">
              ${p.comments.map(c => `
                <div class="flex gap-3 items-start border-b border-orange-100 pb-3">
                  <img src="uploads/photo/${c.profile_photo}" class="w-9 h-9 rounded-full border border-orange-300">
                  <div class="bg-white px-4 py-2 rounded-xl shadow-sm w-full">
                    <div class="flex justify-between items-center">
                      <span class="font-bold text-orange-700 text-sm">${c.name}</span>
                      <span class="text-[10px] text-gray-400">${c.date}</span>
                    </div>
                    <div class="text-gray-700 text-sm mt-1 leading-tight">${c.comment}</div>
                  </div>
                </div>
              `).join('')}
            </div>
          </div>
        </div>
      `;
      container.insertAdjacentHTML("beforeend", postHTML);
      renderedCount++;

      if(renderedCount === 4){
          injectSuggestions(container);
      }
    });

    offset += posts.length;
    
  } catch (error) {
    console.error("Error fetching posts:", error);
  } finally {
    isLoading = false;
  }
}

async function injectSuggestions(container) {
    const res = await fetch('follow_action.php?action=fetch_suggestions&limit=8');
    const data = await res.json();
    if(!data.ok || data.suggestions.length === 0) return;

    const sugHTML = `
        <div class="bg-gradient-to-r from-orange-50 to-white max-w-6xl w-full mx-auto rounded-xl shadow-lg border border-orange-200 p-6 mt-8 mb-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-orange-700 flex items-center gap-2 text-lg">
                    <i class="fa-solid fa-user-plus"></i> People You May Know
                </h3>
                <a href="find_people" class="text-orange-600 font-bold text-sm border-b-2 border-orange-600 hover:text-orange-700">View More</a>
            </div>
            
            <div class="flex gap-4 overflow-x-auto pb-4 no-scrollbar scroll-smooth" style="scrollbar-width: none; -ms-overflow-style: none;">
                ${data.suggestions.map(s => `
                    <div id="sug-${s.id}" class="min-w-[160px] bg-white rounded-xl border border-orange-100 p-4 transition-all hover:shadow-md flex flex-col items-center">
                        <div class="relative mb-3">
                            ${s.profile_photo ? 
                                `<img src="${s.profile_photo}" class="w-16 h-16 rounded-full object-cover border-2 border-orange-400 shadow-sm">` :
                                `<div class="w-16 h-16 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-2xl border-2 border-orange-400 shadow-sm">${s.initials}</div>`
                            }
                            ${s.follows_me ? `<span class="absolute -bottom-1 -right-1 bg-green-500 text-white text-[8px] px-1.5 py-0.5 rounded-full uppercase font-bold border border-white">Follows You</span>` : ''}
                        </div>
                        
                        <a href="user_profile?id=${s.id}" class="font-bold text-gray-800 text-sm text-center line-clamp-1 hover:text-orange-600 transition">${s.name}</a>
                        <p class="text-gray-400 text-[10px] mb-3">${s.city || 'Member'}</p>
                        
                        <div class="flex flex-col gap-1.5 w-full mt-auto">
                            <button onclick="suggestionFollow(${s.id})" class="suggestion-follow-btn w-full bg-orange-500 text-white py-1.5 rounded-lg text-xs font-bold shadow-sm hover:bg-orange-600 transition">
                                ${s.follows_me ? 'Accept Request' : 'Send Request'}
                            </button>
                            <button onclick="suggestionCancel(${s.id}, ${s.follows_me})" class="w-full bg-gray-50 text-gray-400 py-1 rounded-lg text-[10px] uppercase tracking-wider font-bold hover:bg-gray-100 transition whitespace-nowrap">
                                ${s.follows_me ? 'Ignore' : 'Cancel'}
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    container.insertAdjacentHTML("beforeend", sugHTML);
}

async function suggestionFollow(id) {
    const res = await fetch('follow_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=follow&user_id=${id}`
    });
    const data = await res.json();
    if(data.ok){
        const card = document.getElementById(`sug-${id}`);
        const btn = card.querySelector('.suggestion-follow-btn');
        if(data.status === 'requested') {
            btn.innerText = "Requested";
            btn.classList.replace('bg-orange-500', 'bg-gray-400');
            setTimeout(() => card.classList.add('hidden'), 500);
        } else if(data.status === 'connected') {
            btn.innerText = "Friends";
            btn.classList.replace('bg-orange-500', 'bg-green-500');
            setTimeout(() => card.classList.add('hidden'), 500);
        }
    }
}

async function suggestionCancel(id, followsMe) {
    if(followsMe && confirm('Removing them as follower? They will no longer follow you.')){
         await fetch('follow_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=remove_follower&user_id=${id}`
        });
    }
    const card = document.getElementById(`sug-${id}`);
    card.classList.add('hidden');
}

// Initial Load
fetchPosts(true);

// Infinite Scroll
window.addEventListener('scroll', () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
        fetchPosts();
    }
});

// ❤️ Like toggle
document.addEventListener('click', async e => {
  const btn = e.target.closest('.like-btn');
  if(!btn) return;
  const id = btn.dataset.id;
  
  // Optimistic UI Update
  const icon = btn.querySelector('i');
  const countSpan = btn.querySelector('.like-count');
  let count = parseInt(countSpan.textContent);
  
  if (icon.classList.contains('fa-regular')) {
      icon.classList.remove('fa-regular', 'text-gray-400');
      icon.classList.add('fa-solid', 'text-red-500');
      count++;
  } else {
      icon.classList.remove('fa-solid', 'text-red-500');
      icon.classList.add('fa-regular', 'text-gray-400');
      count--;
  }
  countSpan.textContent = count;

  await fetch('like_comment_action.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=like&id=${id}`
  });
  // No need to fetchAll(), just update UI locally as above
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
    
    // Append comment locally
    const commentList = document.querySelector(`#comments-${id} .comment-list`);
    const newCommentHTML = `
        <div class="flex gap-3 items-start border-b border-orange-100 pb-3">
            <img src="uploads/photo/${myPhoto}" class="w-9 h-9 rounded-full border border-orange-300">
            <div class="bg-white px-4 py-2 rounded-xl shadow-sm w-full">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-orange-700 text-sm">${myName}</span>
                    <span class="text-[10px] text-gray-400">Just now</span>
                </div>
                <div class="text-gray-700 text-sm mt-1 leading-tight">${text}</div>
            </div>
        </div>
    `;
    commentList.insertAdjacentHTML('afterbegin', newCommentHTML);
    
    // Update comment count
    const countSpan = document.querySelector(`.comment-toggle[data-id="${id}"] .comment-count`);
    if(countSpan) {
        countSpan.textContent = parseInt(countSpan.textContent) + 1;
    }
});





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

