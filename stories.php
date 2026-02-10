<?php
// stories.php
//session_start();
include("connection.php");

// AUTH
if (!isset($_SESSION['sadhu_user_id'])) {
    header("Location: login.php");
    exit;
}

$user_email = $con->real_escape_string($_SESSION['sadhu_user_id']);
$user_q = $con->query("SELECT id,name,profile_photo FROM tbl_members WHERE email='$user_email' LIMIT 1");
if (!$user_q || $user_q->num_rows === 0) {
    die("User not found.");
}
$user = $user_q->fetch_assoc();
$user_id = (int)$user['id'];

// Fetch users who have stories in last 24 hours, with unseen_count for current viewer
$sql = "
SELECT 
  m.id AS user_id,
  m.name,
  m.profile_photo,
  COUNT(s.id) AS total_stories,
  SUM(CASE WHEN (SELECT COUNT(*) FROM tbl_story_views v WHERE v.story_id = s.id AND v.viewer_id = '$user_id') = 0 THEN 1 ELSE 0 END) AS unseen_count,
  MAX(s.date) AS latest_date
FROM tbl_stories s
JOIN tbl_members m ON s.user_id = m.id
WHERE s.date > (NOW() - INTERVAL 1 DAY)
GROUP BY m.id
ORDER BY latest_date DESC
";
$story_users_q = $con->query($sql);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Stories</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
    /* small custom */
    .story-strip {
      scroll-snap-type: x mandatory;
      -webkit-overflow-scrolling: touch;
    }
    .story-item { scroll-snap-align: start; }
    .no-select { user-select:none; -webkit-user-drag:none; }
    .progress-inner { transition: width linear; }
    /* avoid overwriting global styles */
    #storyViewer .viewer-media img, #storyViewer .viewer-media video { user-select:none; -webkit-user-drag:none; }
  </style>
</head>
<body class="bg-orange-50 min-h-screen">

<!-- Header removed (included on index) -->

<!-- Story strip -->
<section class="max-w-8xl mx-auto mt-4 px-4">
  <div class="flex gap-6 flex-nowrap overflow-x-auto story-strip py-1 no-select">
    <!-- ADD STORY ITEM (left of Your Story) -->
    <div class="story-item flex flex-col items-center w-1/6 sm:w-20 flex-none">
      <form id="addStoryForm" action="story_upload.php" method="POST" enctype="multipart/form-data" class="flex flex-col items-center">
        <label for="storyFile" class="relative cursor-pointer rounded-full">
          <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-white bg-white">
            <img src="uploads/photo/<?php echo htmlspecialchars($user['profile_photo'])?>" alt="You" class="w-full h-full object-cover"/>
          </div>
          <span class="absolute -bottom-0.5 -right-0.5 bg-orange-500 text-white rounded-full w-6 h-6 flex items-center justify-center border-2 border-white">
            <i class="fa-solid fa-plus text-xs"></i>
          </span>
        </label>
        <input id="storyFile" type="file" name="story" accept="image/*,video/*" class="hidden" onchange="document.getElementById('addStoryForm').submit()">
      </form>
      <div class="text-xs text-gray-700 mt-1 text-center">Add</div>
    </div>

    <!-- YOUR STORY ITEM -->
    <?php
      // my totals
      $my_total_q = $con->query("SELECT COUNT(*) AS cnt FROM tbl_stories WHERE user_id='$user_id' AND date > (NOW() - INTERVAL 1 DAY)");
      $my_total = ($my_total_q && $my_total_q->num_rows) ? intval($my_total_q->fetch_assoc()['cnt']) : 0;
      $my_unseen_q = $con->query("SELECT COUNT(*) AS cnt FROM tbl_stories s LEFT JOIN tbl_story_views v ON s.id=v.story_id AND v.viewer_id='$user_id' WHERE s.user_id='$user_id' AND s.date > (NOW() - INTERVAL 1 DAY) AND v.id IS NULL");
      $my_unseen = ($my_unseen_q && $my_unseen_q->num_rows) ? intval($my_unseen_q->fetch_assoc()['cnt']) : 0;
      $my_border = $my_total > 0 ? 'from-pink-500 to-yellow-400' : 'from-blue-400 to-blue-500';
    ?>
    <div class="story-item flex flex-col items-center w-1/6 sm:w-20 flex-none">
      <div class="rounded-full p-0.5 bg-gradient-to-tr <?php echo $my_border ?>">
        <button id="myStoryBtn" class="rounded-full overflow-hidden w-16 h-16 bg-white border-2 border-white">
          <img src="uploads/photo/<?php echo htmlspecialchars($user['profile_photo'])?>" alt="You" class="w-full h-full object-cover"/>
        </button>
      </div>
      <div class="text-xs text-gray-700 mt-1 text-center">
        You
        <?php if($my_total>0): ?>
          <div class="text-[11px] text-gray-500"><?php echo $my_total ?> story<?php echo ($my_total>1)?'s':''; ?></div>
        <?php else: ?>
          <div class="text-[11px] text-gray-400">Add story</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- OTHER USERS -->
    <?php while($su = $story_users_q->fetch_assoc()):
         if($su['user_id'] == $user_id) continue;
         $has_unseen = intval($su['unseen_count']) > 0;
         $border_cls = $has_unseen ? 'from-pink-500 to-yellow-400' : 'bg-gray-300';
    ?>
      <div class="story-item flex flex-col items-center w-1/6 sm:w-20 flex-none">
        <div class="<?php echo ($has_unseen ? "rounded-full p-0.5 bg-gradient-to-tr $border_cls" : "rounded-full p-0.5 bg-gray-200") ?>">
          <button class="otherStoryBtn rounded-full overflow-hidden w-16 h-16 bg-white" 
                  data-userid="<?php echo $su['user_id'] ?>" data-username="<?php echo htmlspecialchars($su['name'])?>">
            <img src="uploads/photo/<?php echo htmlspecialchars($su['profile_photo'])?>" alt="" class="w-full h-full object-cover"/>
          </button>
        </div>
        <div class="text-xs text-gray-700 mt-1 text-center"><?php echo htmlspecialchars($su['name'])?></div>
      </div>
    <?php endwhile; ?>

  </div>
</section>

<!-- Viewer modal -->
<div id="storyViewer" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4">
  <div class="relative max-w-3xl w-full">
    <button id="closeViewer" class="absolute top-3 right-3 text-white text-3xl z-50">&times;</button>

    <!-- progress bars -->
    <div id="progressContainer" class="flex gap-2 px-2 py-3"></div>
    <div class="text-white">
        <span id="viewerUserName" class="font-semibold"></span>
        <div id="viewerTime" class="text-xs text-gray-300"></div>
      </div>
    <!-- media area -->
    <div id="mediaArea" class="relative bg-black rounded-xl overflow-hidden flex items-center justify-center" style="height:70vh;">
      <img id="mediaImg" src="" alt="" class="max-h-full max-w-full object-contain hidden" />
      <video id="mediaVideo" src="" controls autoplay playsinline class="max-h-full max-w-full object-contain hidden" controlsList="nodownload noremoteplayback"></video>

      <!-- tap zones -->
      <div id="tapLeft" class="absolute left-0 top-0 h-full w-1/2"></div>
      <div id="tapRight" class="absolute right-0 top-0 h-full w-1/2"></div>
    </div>

    <div class="flex items-center justify-between mt-3 px-3">
      

      <div class="flex items-center gap-3">
        <button id="deleteStoryBtn" class="hidden text-red-600  px-3 py-1 rounded"><i class="fa fa-solid fa-trash fa-lg"></i></button>
        <div id="viewsBtn" class="text-sm text-gray-300 hidden cursor-pointer"><i class="fa fa-solid fa-eye fa-lg"> </i> <span id="viewsCount"> 0</span></div>
      </div>
    </div>
  </div>
</div>

<!-- viewers modal (overlay small) -->
<div id="viewersModal" class="hidden fixed inset-0 z-60 flex items-center justify-center bg-black/60 p-4">
  <div class="bg-white rounded-lg max-w-md w-full p-4">
    <div class="flex justify-between items-center mb-3">
      <h3 class="font-semibold">Seen by</h3>
      <button id="closeViewersBtn" class="text-gray-500">&times;</button>
    </div>
    <div id="viewersList" class="space-y-2 max-h-64 overflow-auto"></div>
  </div>
</div>

<script>
/* -------------------------
   Client-side story viewer
   ------------------------- */

const sessionUserId = "<?php echo $user_id ?>";
let stories = []; // loaded stories for the selected user
let currentIndex = 0;
let autoTimer = null;
let paused = false;

const viewer = document.getElementById('storyViewer');
const mediaImg = document.getElementById('mediaImg');
const mediaVideo = document.getElementById('mediaVideo');
const progressContainer = document.getElementById('progressContainer');
const viewerUserName = document.getElementById('viewerUserName');
const viewerTime = document.getElementById('viewerTime');
const deleteBtn = document.getElementById('deleteStoryBtn');
const viewsBtn = document.getElementById('viewsBtn');
const viewsCount = document.getElementById('viewsCount');
const viewersModal = document.getElementById('viewersModal');
const viewersList = document.getElementById('viewersList');

function clearAuto(){
  if(autoTimer) { clearTimeout(autoTimer); autoTimer = null; }
}
function stopVideo(){
  try { mediaVideo.pause(); mediaVideo.currentTime = 0; mediaVideo.src = ''; } catch(e){}
}
function hideAllMedia(){
  mediaImg.classList.add('hidden');
  mediaVideo.classList.add('hidden');
  stopVideo();
}

/* build progress bars UI */
function buildProgressBars(){
  progressContainer.innerHTML = '';
  stories.forEach((s,i)=>{
    const w = document.createElement('div');
    w.className = 'flex-1 bg-gray-600 rounded overflow-hidden';
    w.style.height = '4px';
    const inner = document.createElement('div');
    inner.id = 'prog-'+i;
    inner.className = 'bg-white h-full progress-inner';
    inner.style.width = '0%';
    w.appendChild(inner);
    progressContainer.appendChild(w);
  });
}

/* fetch stories for a user and open viewer */
async function openStoriesFor(userId, userName){
  try{
    const res = await fetch('fetch_stories.php?user_id='+encodeURIComponent(userId));
    stories = await res.json();
    if(!Array.isArray(stories) || stories.length === 0) return;
    // prefer latest-first inside viewer -> keep server order (assumed latest-first)
    currentIndex = 0;
    viewerUserName.textContent = userName || '';
    viewerTime.textContent = '';
    deleteBtn.style.display = (String(userId) === String(sessionUserId)) ? 'inline-block' : 'none';
    viewsBtn.style.display = (String(userId) === String(sessionUserId)) ? 'inline-block' : 'none';
    // Batch mark all fetched stories as viewed immediately when viewer opens
    try{
      const ids = stories.map(s=>s.id);
      fetch('story_views.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'story_ids='+encodeURIComponent(JSON.stringify(ids))
      }).catch(()=>{});
    } catch(e){ /* ignore */ }

    buildProgressBars();
    showStory(0);
    viewer.classList.remove('hidden');
  } catch(e) {
    console.error(e);
  }
}

/* show story by index */
function showStory(idx){
  if(idx < 0) idx = 0;
  if(idx >= stories.length){
    closeViewer();
    return;
  }
  currentIndex = idx;
  const s = stories[idx];
  hideAllMedia();
  clearAuto();

  // set timestamp if available
  if(s.date) viewerTime.textContent = new Date(s.date).toLocaleString();
  else viewerTime.textContent = '';

  // show image or video
  if(s.type === 'image'){
    mediaImg.src = s.media;
    mediaImg.classList.remove('hidden');
    // animate progress bar for 10s
    startProgress(10000);
  } else { // video
    mediaVideo.src = s.media;
    mediaVideo.classList.remove('hidden');
    mediaVideo.play().catch(()=>{});
    // when metadata available, set duration
    mediaVideo.onloadedmetadata = function(){
      const dms = Math.floor(mediaVideo.duration * 1000) || 10000;
      startProgress(dms);
    };
    // if video ends naturally, go next
    mediaVideo.onended = function(){ nextStory(); };
  }

  // mark as viewed (call backend)
  markViewed(s.id);

  // update viewers count for owner (optional)
  if(deleteBtn.style.display !== 'none'){
    fetch('fetch_story_viewers.php?story_id='+encodeURIComponent(s.id))
      .then(r=>r.json())
      .then(j=>{
        viewsCount.textContent = Array.isArray(j) ? j.length : (s.views || 0);
      }).catch(()=>{ viewsCount.textContent = s.views || 0; });
  } else {
    viewsCount.textContent = s.views || 0;
  }

  // update bars UI
  for(let i=0;i<stories.length;i++){
    const el = document.getElementById('prog-'+i);
    if(!el) continue;
    if(i < idx) { el.style.width = '100%'; el.style.transition = 'none'; }
    else if(i === idx) { el.style.width = '0%'; el.style.transition = 'none'; /* set later in startProgress */ }
    else { el.style.width = '0%'; el.style.transition = 'none'; }
  }
}

/* start progress animation and auto-next after ms */
function startProgress(ms){
  ms = ms || 10000;
  const el = document.getElementById('prog-'+currentIndex);
  if(!el) return;
  // force reflow then animate
  el.style.transition = 'none';
  el.style.width = '0%';
  setTimeout(()=>{
    el.style.transition = `width ${ms}ms linear`;
    el.style.width = '100%';
  },30);

  clearAuto();
  autoTimer = setTimeout(()=> {
    nextStory();
  }, ms);
}

/* mark viewed via story_views.php */
function markViewed(storyId){
  // fire-and-forget POST
  fetch('story_views.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'story_id='+encodeURIComponent(storyId)
  }).catch(()=>{});
}

/* controls */
function nextStory(){
  if(currentIndex < stories.length - 1) showStory(currentIndex+1);
  else closeViewer();
}
function prevStory(){
  if(currentIndex > 0) showStory(currentIndex-1);
  else showStory(0);
}
function closeViewer(){
  viewer.classList.add('hidden');
  clearAuto();
  stopVideo();
}

/* delete story (owner) */
document.getElementById('deleteStoryBtn').addEventListener('click', async ()=>{
  if(!confirm('Delete this story?')) return;
  const s = stories[currentIndex];
  try{
    const res = await fetch('story_delete.php?story_id='+encodeURIComponent(s.id));
    if(res.ok) location.reload();
    else alert('Delete failed');
  } catch(e){ alert('Delete failed'); }
});

/* close button for story viewer */
document.getElementById('closeViewer').addEventListener('click', ()=>{ closeViewer(); });

/* views button opens small modal */
document.getElementById('viewsBtn').addEventListener('click', async ()=>{
  const s = stories[currentIndex];
  if(!s) return;
  // pause playback and mark this story as viewed before showing the viewers list
  try{ pausePlayback(); }catch(e){}
  try{ markViewed(s.id); }catch(e){}
  viewersList.innerHTML = 'Loading...';
  viewersModal.classList.remove('hidden');
  try{
    const res = await fetch('fetch_story_viewers.php?story_id='+encodeURIComponent(s.id));
    const j = await res.json();
    if(Array.isArray(j) && j.length){
      viewersList.innerHTML = j.map(v => `
        <div onclick="location.href='user_profile.php?id=${encodeURIComponent(v.id)}'" class="flex items-center gap-3 py-2 border-b cursor-pointer">
          <img src="uploads/photo/${v.profile||'default.png'}" class="w-8 h-8 rounded-full object-cover" />
          <div>
            <div class="font-medium">${v.name}</div>
            <div class="text-xs text-gray-500">${v.email || ''}</div>
          </div>
        </div>
      `).join('');
    } else {
      viewersList.innerHTML = '<div class="text-sm text-gray-500">No viewers yet.</div>';
    }
  } catch(e){
    viewersList.innerHTML = '<div class="text-sm text-red-500">Failed to load viewers.</div>';
  }
});

/* attach click events for story items */
document.getElementById('myStoryBtn').addEventListener('click', function(e){
  e.stopPropagation();
  openStoriesFor(<?php echo json_encode($user_id); ?>, <?php echo json_encode($user['name']); ?>);
});
document.querySelectorAll('.otherStoryBtn').forEach(btn=>{
  btn.addEventListener('click', function(e){
    e.stopPropagation();
    openStoriesFor(this.dataset.userid, this.dataset.username);
  });
});

/* tap zones */
document.getElementById('tapLeft').addEventListener('click', ()=>{ prevStory(); });
document.getElementById('tapRight').addEventListener('click', ()=>{ nextStory(); });

/* long-press to pause/resume (desktop touch aware) */
let holdTimer = null;
const mediaArea = document.getElementById('mediaArea');
mediaArea.addEventListener('touchstart', ()=>{ holdTimer = setTimeout(pausePlayback, 350); }, {passive:true});
mediaArea.addEventListener('touchend', ()=>{ if(holdTimer) clearTimeout(holdTimer); if(paused) resumePlayback(); });
mediaArea.addEventListener('mousedown', ()=>{ holdTimer = setTimeout(pausePlayback, 350); });
mediaArea.addEventListener('mouseup', ()=>{ if(holdTimer) clearTimeout(holdTimer); if(paused) resumePlayback(); });

function pausePlayback(){
  paused = true;
  clearAuto();
  try{ mediaVideo.pause(); }catch(e){}
  // freeze current prog bar by removing transition
  const el = document.getElementById('prog-'+currentIndex);
  if(el){
    const computed = window.getComputedStyle(el).width;
    el.style.transition = 'none';
    el.style.width = computed;
  }
}
function resumePlayback(){
  paused = false;
  // resume video or image
  if(!mediaVideo.classList.contains('hidden')){
    try{ mediaVideo.play().catch(()=>{}); }catch(e){}
    // resume progress using remaining duration
    const remain = (mediaVideo.duration - mediaVideo.currentTime) * 1000;
    startProgress(remain > 0 ? remain : 3000);
  } else {
    // image: restart shorter remaining
    startProgress(5000);
  }
}

/* keyboard nav for desktop */
document.addEventListener('keydown', (e)=>{
  if(viewer.classList.contains('hidden')) return;
  if(e.key === 'ArrowLeft') prevStory();
  if(e.key === 'ArrowRight') nextStory();
  if(e.key === 'Escape') closeViewer();
});

/* viewers modal close: resume playback when modal closed */
document.getElementById('closeViewersBtn').addEventListener('click', ()=>{
  viewersModal.classList.add('hidden');
  // resume playback if it was paused by opening the viewers modal
  try{ resumePlayback(); }catch(e){}
});
</script>

</body>
</html>
