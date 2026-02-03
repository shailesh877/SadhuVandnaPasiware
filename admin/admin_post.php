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
$posts = mysqli_query($con, "
SELECT p.*, m.name, m.mobile
FROM tbl_posts p
LEFT JOIN tbl_members m ON m.id = p.user_id
ORDER BY p.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - All Posts</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>

<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen">

<!-- HEADER -->
<header class="bg-white shadow sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-4 py-4 space-y-3">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <a href="index" class="w-9 h-9 flex items-center justify-center bg-orange-100 rounded-lg">
          <i class="fa-solid fa-arrow-left text-orange-600"></i>
        </a>
        <h1 class="text-xl font-bold">All Posts</h1>
      </div>
      <span class="px-3 py-1 bg-orange-100 text-orange-600 rounded-full text-sm">
        <?= mysqli_num_rows($posts) ?> Posts
      </span>
    </div>

    <input
      type="search"
      id="searchInput"
      placeholder="Search by name, mobile or post..."
      class="w-full border rounded-lg px-4 py-2 text-sm"
    />
  </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6">

<?php if(isset($_SESSION['msg'])): ?>
<div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
  <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
</div>
<?php endif; ?>

<!-- DESKTOP TABLE -->
<div class="hidden md:block bg-white rounded-xl shadow overflow-hidden">
<div class="max-h-[70vh] overflow-y-auto">
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

</main>

<!-- MEDIA MODAL -->
<div id="mediaModal" class="fixed inset-0 bg-black bg-opacity-90 hidden items-center justify-center z-50">
<span id="closeMediaModal" class="absolute top-5 right-6 text-white text-3xl cursor-pointer">&times;</span>
<img id="modalImg" class="hidden max-w-full max-h-full rounded">
<video id="modalVideo" class="hidden max-w-full max-h-full rounded" controls></video>
</div>

<script>
// SEARCH
document.getElementById("searchInput").addEventListener("input", function(){
  let v=this.value.toLowerCase();
  document.querySelectorAll("tbody tr").forEach(r=>{
    r.style.display=r.innerText.toLowerCase().includes(v)?"":"none";
  });
  document.querySelectorAll(".mobile-card").forEach(c=>{
    c.style.display=c.dataset.search.includes(v)?"":"none";
  });
});

// MEDIA MODAL
const modal=document.getElementById("mediaModal"),
img=document.getElementById("modalImg"),
vid=document.getElementById("modalVideo"),
close=document.getElementById("closeMediaModal");

document.querySelectorAll(".view-img").forEach(e=>{
e.onclick=()=>{vid.pause();vid.classList.add("hidden");img.src=e.src;img.classList.remove("hidden");modal.classList.add("flex");modal.classList.remove("hidden");};
});
document.querySelectorAll(".view-video").forEach(e=>{
e.onclick=()=>{img.classList.add("hidden");vid.src=e.src;vid.classList.remove("hidden");vid.play();modal.classList.add("flex");modal.classList.remove("hidden");};
});
close.onclick=()=>{vid.pause();vid.src="";modal.classList.add("hidden");modal.classList.remove("flex");};
modal.onclick=e=>{if(e.target===modal)close.click();};
</script>

</body>
</html>