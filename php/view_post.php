<?php
include("header.php");
?>
<main class="flex-1 px-2 md:px-10 py-15 md:ml-20 mb-13 md:mb-0 max-w-7xl overflow-hidden min-h-screen">
    <div id="singlePostContainer" class="flex flex-col gap-4 flex-1">
        <div class="text-center mt-10 text-gray-500">Loading Post...</div>
    </div>
</main>

<script>
async function fetchPost() {
    const urlParams = new URLSearchParams(window.location.search);
    const pid = urlParams.get('id');
    if(!pid) {
        document.getElementById('singlePostContainer').innerHTML = "<div class='text-center text-red-500 mt-10'>Invalid Link</div>";
        return;
    }

    const res = await fetch(`like_comment_action.php?action=fetch_one&id=${pid}`);
    const posts = await res.json();
    
    if(posts.length === 0){
        document.getElementById('singlePostContainer').innerHTML = "<div class='text-center text-red-500 mt-10'>Post not found</div>";
        return;
    }

    const p = posts[0];
    const container = document.getElementById("singlePostContainer");
    
    const likedClass = p.user_liked ? 'fa-solid text-red-500' : 'fa-regular text-gray-400';

    container.innerHTML = `
      <div class="bg-white max-w-4xl mx-auto rounded-xl shadow-lg border border-orange-200 px-6 py-5 mt-5">
        <div class="flex items-center gap-4">
          <a href="#" class="flex items-center gap-3">
            <img src="uploads/photo/${p.profile_photo}" class="w-10 h-10 rounded-full border-2 border-orange-300">
            <div>
              <div class="font-bold text-orange-700">${p.name}</div>
              <div class="text-xs text-gray-500">${p.date}</div>
            </div>
          </a>
        </div>

        <div class="mt-3 text-gray-800 text-lg">${p.status}</div>
        ${p.link ? `<div class="mt-2 text-blue-600 break-all"><a href="${p.link}" target="_blank">${p.link}</a></div>` : ''}

        ${p.media.map(m => m.match(/\.(jpg|jpeg|png|gif)$/i) ? 
          `<img src="uploads/posts/${m}" class="rounded-xl mt-3 max-h-[600px] w-full object-contain mx-auto">` : 
          `<video src="uploads/posts/${m}" class="rounded-xl mt-3 w-full max-h-[600px] mx-auto" controls></video>`).join('')}

        <div class="flex gap-6 mt-4 pt-4 border-t border-gray-100 text-gray-700 text-base">
            <span class="flex items-center gap-1"><i class="${likedClass} fa-heart text-lg"></i> ${p.likes}</span>
            <span class="flex items-center gap-1"><i class="fa-regular fa-comment-dots"></i> ${p.comments.length}</span>
        </div>
        
        <div class="text-center mt-6">
            <a href="index.php" class="bg-orange-600 text-white px-6 py-2 rounded-full font-bold shadow hover:bg-orange-700 transition">
                View More Posts / Download App
            </a>
        </div>
      </div>
    `;
}
fetchPost();
</script>
</body>
</html>
