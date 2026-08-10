<?php
session_start();
include("../connection.php");

/* DELETE POST */
if (isset($_POST['action'], $_POST['id']) && $_POST['action'] === "delete") {
    $id = intval($_POST['id']);

    $q = mysqli_query($con, "SELECT media FROM tbl_posts WHERE id=$id");
    if ($row = mysqli_fetch_assoc($q)) {
        if ($row['media'] && file_exists("../uploads/posts/" . $row['media'])) {
            unlink("../uploads/posts/" . $row['media']);
        }
    }

    mysqli_query($con, "DELETE FROM tbl_posts WHERE id=$id");
    $_SESSION['msg'] = "Post deleted successfully!";
    header("Location: admin_post");
    exit;
}

/* FETCH POSTS */
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';
$whereClause = "";
if (!empty($search)) {
    $whereClause = " WHERE p.status LIKE '%$search%' OR m.name LIKE '%$search%' OR m.mobile LIKE '%$search%' ";
}

$total_q = mysqli_query($con, "SELECT COUNT(*) as cnt FROM tbl_posts p LEFT JOIN tbl_members m ON m.id = p.user_id $whereClause");
$total_row = mysqli_fetch_assoc($total_q);
$total_posts = $total_row['cnt'];
$total_pages = ceil($total_posts / $limit);

$posts = mysqli_query($con, "
SELECT p.*, m.name, m.mobile
FROM tbl_posts p
LEFT JOIN tbl_members m ON m.id = p.user_id
$whereClause
ORDER BY p.created_at DESC
LIMIT $offset, $limit
");

if (isset($_GET['ajax'])) {
    $response = [
        'desktop' => '',
        'mobile' => '',
        'has_more' => ($page < $total_pages)
    ];
    $i = $offset + 1;
    while($p = mysqli_fetch_assoc($posts)) {
        // Desktop HTML
        $mediaHtml = '';
        if($p['media']){
            if(preg_match('/mp4|webm|ogg/i',$p['media'])){
                $mediaHtml = '<video src="../uploads/posts/'.$p['media'].'" class="w-28 rounded cursor-pointer view-video" muted></video>';
            }else{
                $mediaHtml = '<img src="../uploads/posts/'.$p['media'].'" class="w-20 rounded cursor-pointer view-img">';
            }
        }
        $linkHtml = $p['link'] ? '<a href="'.$p['link'].'" target="_blank" class="text-blue-600 underline">Open</a>' : '';
        $dateHtml = date("d M Y",strtotime($p['created_at']));
        $name = htmlspecialchars($p['name'] ?? 'Unknown');
        $mobile = htmlspecialchars($p['mobile'] ?? '-');
        $status = htmlspecialchars($p['status']);
        $id = $p['id'];
        
        $response['desktop'] .= '<tr class="hover:bg-orange-50">
<td class="px-4 py-3">'.$i.'</td>
<td class="px-4 py-3"><div class="font-semibold">'.$name.'</div><div class="text-xs text-gray-500">'.$mobile.'</div></td>
<td class="px-4 py-3">'.$status.'</td>
<td class="px-4 py-3">'.$mediaHtml.'</td>
<td class="px-4 py-3">'.$linkHtml.'</td>
<td class="px-4 py-3">'.$dateHtml.'</td>
<td class="px-4 py-3 text-center">
<form method="post" onsubmit="return confirm(\'Delete this post?\')">
<input type="hidden" name="id" value="'.$id.'">
<button name="action" value="delete" class="w-8 h-8 bg-red-100 text-red-600 rounded"><i class="fa-solid fa-trash"></i></button>
</form>
</td>
</tr>';

        // Mobile HTML
        $response['mobile'] .= '<div class="bg-white rounded-xl shadow p-4 mobile-card">
<h3 class="font-bold">'.$name.'</h3>
<p class="text-xs text-gray-500 mb-1">'.$mobile.'</p>
<p class="text-sm mb-2">'.$status.'</p>';
        if($p['media']){
            if(preg_match('/mp4|webm|ogg/i',$p['media'])){
                $response['mobile'] .= '<video src="../uploads/posts/'.$p['media'].'" class="w-full rounded mb-2 cursor-pointer view-video" muted></video>';
            }else{
                $response['mobile'] .= '<img src="../uploads/posts/'.$p['media'].'" class="w-full rounded mb-2 cursor-pointer view-img">';
            }
        }
        $response['mobile'] .= '<form method="post" onsubmit="return confirm(\'Delete this post?\')">
<input type="hidden" name="id" value="'.$id.'">
<button name="action" value="delete" class="w-full bg-red-100 text-red-600 py-2 rounded">Delete Post</button>
</form>
</div>';
        $i++;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<?php include("header.php"); ?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">

<?php if(isset($_SESSION['msg'])): ?>
<div class="mb-2 p-1 bg-green-100 text-green-700 rounded text-sm text-center font-semibold border border-green-300">
  <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
</div>
<?php endif; ?>

<!-- DESKTOP TABLE -->
<div class="hidden md:block bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
  
  <div class="p-2 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50">
    <div class="flex items-center gap-3">
      <a href="index.php" class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
        <i class="fa-solid fa-arrow-left text-orange-600 text-sm"></i>
      </a>
      <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
        All Posts
      </h2>
    </div>
    
    <div class="flex items-center gap-2 w-full sm:w-auto">
      <form method="get" class="relative w-full sm:w-64">
        <i class="fa-solid fa-search absolute left-3 top-2.5 text-orange-400 text-sm"></i>
        <input 
          type="search" 
          name="search" 
          value="<?= htmlspecialchars($search) ?>"
          placeholder="Search..." 
          class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 shadow-sm rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition text-sm text-gray-700"
        >
      </form>
      <div class="text-sm font-medium text-gray-500 bg-white px-3 py-1.5 rounded-lg border shadow-sm whitespace-nowrap">
        Total: <span class="text-orange-600 font-bold"><?= $total_posts ?></span>
      </div>
    </div>
  </div>

<div class="overflow-y-auto" style="max-height: calc(100vh - 130px);">
<table class="w-full text-sm">
<thead class="bg-orange-600 text-white sticky top-0 z-20">
<tr>
  <th class="px-4 py-3">#</th>
  <th class="px-4 py-3">Member</th>
  <th class="px-4 py-3">Post</th>
  <th class="px-4 py-3">Media</th>
  <th class="px-4 py-3">Link</th>
  <th class="px-4 py-3">Date</th>
  <th class="px-4 py-3 text-center">Action</th>
</tr>
</thead>
<tbody>
<?php $i=1; while($p=mysqli_fetch_assoc($posts)): ?>
<tr class="hover:bg-orange-50">
<td class="px-4 py-3"><?= $i ?></td>

<td class="px-4 py-3">
  <div class="font-semibold"><?= htmlspecialchars($p['name'] ?? 'Unknown') ?></div>
  <div class="text-xs text-gray-500"><?= htmlspecialchars($p['mobile'] ?? '-') ?></div>
</td>

<td class="px-4 py-3"><?= htmlspecialchars($p['status']) ?></td>

<td class="px-4 py-3">
<?php if($p['media']): ?>
<?php if(preg_match('/mp4|webm|ogg/i',$p['media'])): ?>
<video src="../uploads/posts/<?= $p['media'] ?>" class="w-28 rounded cursor-pointer view-video" muted></video>
<?php else: ?>
<img src="../uploads/posts/<?= $p['media'] ?>" class="w-20 rounded cursor-pointer view-img">
<?php endif; ?>
<?php endif; ?>
</td>

<td class="px-4 py-3">
<?php if($p['link']): ?>
<a href="<?= $p['link'] ?>" target="_blank" class="text-blue-600 underline">Open</a>
<?php endif; ?>
</td>

<td class="px-4 py-3"><?= date("d M Y",strtotime($p['created_at'])) ?></td>

<td class="px-4 py-3 text-center">
<form method="post" onsubmit="return confirm('Delete this post?')">
<input type="hidden" name="id" value="<?= $p['id'] ?>">
<button name="action" value="delete" class="w-8 h-8 bg-red-100 text-red-600 rounded">
<i class="fa-solid fa-trash"></i>
</button>
</form>
</td>
</tr>
<?php $i++; endwhile; ?>
</tbody>
</table>
</div>
</div>

<!-- MOBILE CARDS -->
<div class="md:hidden space-y-4" id="mobileCards">
<?php mysqli_data_seek($posts,0); while($p=mysqli_fetch_assoc($posts)): ?>
<div class="bg-white rounded-xl shadow p-4 mobile-card"
     data-search="<?= strtolower(($p['name']??'').' '.($p['mobile']??'').' '.$p['status']) ?>">

<h3 class="font-bold"><?= htmlspecialchars($p['name'] ?? 'Unknown') ?></h3>
<p class="text-xs text-gray-500 mb-1"><?= htmlspecialchars($p['mobile'] ?? '-') ?></p>
<p class="text-sm mb-2"><?= htmlspecialchars($p['status']) ?></p>

<?php if($p['media']): ?>
<?php if(preg_match('/mp4|webm|ogg/i',$p['media'])): ?>
<video src="../uploads/posts/<?= $p['media'] ?>" class="w-full rounded mb-2 cursor-pointer view-video" muted></video>
<?php else: ?>
<img src="../uploads/posts/<?= $p['media'] ?>" class="w-full rounded mb-2 cursor-pointer view-img">
<?php endif; ?>
<?php endif; ?>

<form method="post" onsubmit="return confirm('Delete this post?')">
<input type="hidden" name="id" value="<?= $p['id'] ?>">
<button name="action" value="delete" class="w-full bg-red-100 text-red-600 py-2 rounded">
Delete Post
</button>
</form>
</div>
<?php endwhile; ?>
</div>

<!-- LOADING SENTINEL -->
<div id="loadingSentinel" class="py-4 text-center text-gray-500 font-medium">
    <i class="fa-solid fa-spinner fa-spin text-orange-600 mr-2"></i> Loading more posts...
</div>

</main>

<!-- MEDIA MODAL -->
<div id="mediaModal" class="fixed inset-0 bg-black bg-opacity-90 hidden items-center justify-center z-50">
<span id="closeMediaModal" class="absolute top-5 right-6 text-white text-3xl cursor-pointer">&times;</span>
<img id="modalImg" class="hidden max-w-full max-h-full rounded">
<video id="modalVideo" class="hidden max-w-full max-h-full rounded" controls></video>
</div>

<script>
// INFINITE SCROLL
let currentPage = <?= $page ?>;
let totalPages = <?= $total_pages ?>;
let isLoading = false;
let searchQuery = "<?= urlencode($search) ?>";

const desktopTbody = document.querySelector("tbody");
const mobileContainer = document.getElementById("mobileCards");
const sentinel = document.getElementById("loadingSentinel");
const desktopTableContainer = document.querySelector(".overflow-y-auto");

if (currentPage >= totalPages) {
    sentinel.style.display = "none";
}

function loadMore() {
    if (isLoading || currentPage >= totalPages) return;
    isLoading = true;
    currentPage++;
    fetch(`?ajax=1&page=${currentPage}&search=${searchQuery}`)
        .then(r => r.json())
        .then(data => {
            desktopTbody.insertAdjacentHTML('beforeend', data.desktop);
            mobileContainer.insertAdjacentHTML('beforeend', data.mobile);
            isLoading = false;
            if (!data.has_more) {
                if(sentinel) sentinel.style.display = "none";
            } else {
                // Check if we need to keep loading because the container still doesn't overflow
                setTimeout(() => {
                    let shouldLoad = false;
                    if (sentinel) {
                        const rect = sentinel.getBoundingClientRect();
                        if (rect.top >= 0 && rect.top <= window.innerHeight + 100) shouldLoad = true;
                    }
                    if (desktopTableContainer && (desktopTableContainer.scrollHeight - desktopTableContainer.scrollTop - desktopTableContainer.clientHeight < 100)) {
                        shouldLoad = true;
                    }
                    if (shouldLoad) loadMore();
                }, 100);
            }
        })
        .catch(e => { console.error(e); isLoading = false; });
}

// Observer for mobile (body scroll)
const observer = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) loadMore();
}, { rootMargin: "100px" });
if (sentinel) observer.observe(sentinel);

// For desktop table container scroll
if (desktopTableContainer) {
    desktopTableContainer.addEventListener("scroll", function() {
        if (this.scrollHeight - this.scrollTop - this.clientHeight < 100) {
            loadMore();
        }
    });
}

// MEDIA MODAL
const modal=document.getElementById("mediaModal"),
img=document.getElementById("modalImg"),
vid=document.getElementById("modalVideo"),
close=document.getElementById("closeMediaModal");

document.body.addEventListener('click', function(e) {
    if (e.target.classList.contains('view-img')) {
        vid.pause(); vid.classList.add("hidden"); 
        img.src = e.target.src; img.classList.remove("hidden");
        modal.classList.add("flex"); modal.classList.remove("hidden");
    } else if (e.target.classList.contains('view-video')) {
        img.classList.add("hidden"); 
        vid.src = e.target.src; vid.classList.remove("hidden"); vid.play();
        modal.classList.add("flex"); modal.classList.remove("hidden");
    }
});

close.onclick=()=>{vid.pause();vid.src="";modal.classList.add("hidden");modal.classList.remove("flex");};
modal.onclick=e=>{if(e.target===modal)close.click();};
</script>

</body>
</html>
