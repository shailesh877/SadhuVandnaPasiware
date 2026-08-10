<?php
session_start();
include("header.php");

// Ensure tbl_reports table exists
$createTableQuery = "CREATE TABLE IF NOT EXISTS `tbl_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  `reason` text,
  `date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($con, $createTableQuery);

// Ensure tbl_posts has is_suspended column
$checkColumn = mysqli_query($con, "SHOW COLUMNS FROM tbl_posts LIKE 'is_suspended'");
if (mysqli_num_rows($checkColumn) == 0) {
    mysqli_query($con, "ALTER TABLE tbl_posts ADD COLUMN is_suspended TINYINT(1) DEFAULT 0 AFTER media");
}

// Handle Suspend/Activate
if (isset($_POST['action']) && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);
    $action = $_POST['action'];

    if ($action === "suspend") {
        $q = mysqli_query($con, "UPDATE tbl_posts SET is_suspended = 1 WHERE id=$post_id");
        if ($q) $_SESSION['msg'] = "Post suspended successfully!";
        else $_SESSION['msg'] = "Database error!";
    } elseif ($action === "activate") {
        $q = mysqli_query($con, "UPDATE tbl_posts SET is_suspended = 0 WHERE id=$post_id");
        if ($q) $_SESSION['msg'] = "Post activated successfully!";
        else $_SESSION['msg'] = "Database error!";
    }
    header("Location: admin_reported_posts.php");
    exit;
}

// Fetch reported posts
$reportsQuery = "
    SELECT 
        r.id AS report_id, 
        r.reason, 
        r.date AS report_date, 
        p.id AS post_id, 
        p.status AS post_text, 
        p.media, 
        p.is_suspended, 
        m_poster.name AS poster_name, 
        m_reporter.name AS reporter_name 
    FROM tbl_reports r
    JOIN tbl_posts p ON r.post_id = p.id
    JOIN tbl_members m_poster ON p.user_id = m_poster.id
    JOIN tbl_members m_reporter ON r.user_id = m_reporter.id
    ORDER BY r.date DESC
";
$reports = mysqli_query($con, $reportsQuery);
?>



<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">

<!-- TOP TITLE -->
<!-- <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
  <div class="flex items-center gap-3">
    <a href="index.php" class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
      <i class="fa-solid fa-arrow-left text-orange-600"></i>
    </a>
    <div>
      <h1 class="text-xl md:text-2xl font-bold text-gray-800">Reported Posts</h1>
      <p class="text-xs text-gray-500">Manage user reports and moderate content</p>
    </div>
  </div> -->
</div>
  <?php if(isset($_SESSION['msg'])): ?>
    <div class="mb-6 p-1 rounded-xl bg-green-100 border border-green-300 text-green-700 font-semibold flex items-center justify-between shadow-sm">
       <span><i class="fa-solid fa-circle-check mr-2"></i> <?= $_SESSION['msg'] ?></span>
       <button onclick="this.parentElement.style.display='none'" class="text-green-600 hover:text-green-800"><i class="fa-solid fa-times"></i></button>
    </div>
    <?php unset($_SESSION['msg']); ?>
  <?php endif; ?>

  <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
    <div class="p-2 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50">
       <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
         <i class="fa-solid fa-flag text-red-500"></i> All Reports
       </h2>
       <div class="text-sm font-medium text-gray-500 bg-white px-4 py-2 rounded-lg border shadow-sm">
         Total Reports: <span class="text-orange-600 font-bold"><?= mysqli_num_rows($reports) ?></span>
       </div>
    </div>

    <div class="table-container p-0 overflow-y-auto" style="max-height: calc(100vh - 130px);">
      <?php if(mysqli_num_rows($reports) > 0): ?>
      <table class="w-full text-left border-collapse">
        <thead class="bg-gray-100 sticky top-0 z-10 shadow-sm">
          <tr>
            <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Report Info</th>
            <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider w-1/3">Post Content</th>
            <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Status</th>
            <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php while($row = mysqli_fetch_assoc($reports)): ?>
          <tr class="hover:bg-orange-50/50 transition-colors">
            
            <!-- Report Info -->
            <td class="py-4 px-6 align-top">
              <div class="flex flex-col gap-1">
                <span class="text-sm font-bold text-gray-800"><i class="fa-solid fa-user-shield text-orange-500 mr-1"></i> <?= htmlspecialchars($row['reporter_name']) ?></span>
                <span class="text-xs text-gray-500">Reported <span class="font-bold text-orange-600"><?= htmlspecialchars($row['poster_name']) ?></span>'s post</span>
                <span class="text-xs text-gray-400"><i class="fa-regular fa-clock"></i> <?= date("d M Y, h:i A", strtotime($row['report_date'])) ?></span>
                <div class="mt-2 bg-red-50 p-2 rounded border border-red-100 text-sm text-red-800">
                  <span class="font-bold">Reason:</span> <?= nl2br(htmlspecialchars($row['reason'])) ?>
                </div>
              </div>
            </td>

            <!-- Post Content -->
            <td class="py-4 px-6 align-top">
              <div class="text-sm text-gray-700 bg-gray-50 p-3 rounded-xl border border-gray-200">
                <p class="mb-2 whitespace-pre-wrap line-clamp-2 text-[13px]" id="desc-<?= $row['post_id'] ?>-<?= $row['report_id'] ?>"><?= htmlspecialchars($row['post_text']) ?></p>
                <?php if(strlen($row['post_text']) > 100): ?>
                  <button type="button" onclick="toggleDescription('desc-<?= $row['post_id'] ?>-<?= $row['report_id'] ?>', this)" class="text-orange-600 text-xs font-bold hover:underline mb-2">See More</button>
                <?php endif; ?>
                
                <?php if(!empty($row['media'])): ?>
                  <div class="flex gap-2 flex-wrap mt-2">
                    <?php 
                      $mediaFiles = explode(',', $row['media']);
                      foreach($mediaFiles as $mediaFile): 
                        $mediaFile = trim($mediaFile);
                        $mediaPath = "../uploads/posts/" . $mediaFile;
                        if(preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $mediaFile)):
                    ?>
                      <div class="w-16 h-16 rounded overflow-hidden border cursor-pointer hover:opacity-80 transition" onclick="openMediaModal('<?= $mediaPath ?>', 'image')">
                        <img src="<?= $mediaPath ?>" class="w-full h-full object-cover">
                      </div>
                    <?php elseif(preg_match('/\.(mp4|webm|ogg)$/i', $mediaFile)): ?>
                      <div class="w-16 h-16 bg-black rounded flex items-center justify-center border text-white text-xs cursor-pointer hover:bg-gray-800 transition" onclick="openMediaModal('<?= $mediaPath ?>', 'video')">
                        <i class="fa-solid fa-play text-xl"></i>
                      </div>
                    <?php endif; endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </td>

            <!-- Status -->
            <td class="py-4 px-6 text-center align-top">
               <?php if($row['is_suspended'] == 1): ?>
                 <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold border border-red-200 shadow-sm">
                   <i class="fa-solid fa-ban"></i> Suspended
                 </span>
               <?php else: ?>
                 <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200 shadow-sm">
                   <i class="fa-solid fa-check-circle"></i> Active
                 </span>
               <?php endif; ?>
            </td>

            <!-- Actions -->
            <td class="py-4 px-6 text-center align-top">
               <form method="POST" onsubmit="return confirm('Are you sure you want to change the status of this post?');">
                 <input type="hidden" name="post_id" value="<?= $row['post_id'] ?>">
                 <?php if($row['is_suspended'] == 1): ?>
                    <input type="hidden" name="action" value="activate">
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md transition-all w-full flex items-center justify-center gap-2">
                       <i class="fa-solid fa-check"></i> Activate
                    </button>
                 <?php else: ?>
                    <input type="hidden" name="action" value="suspend">
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md transition-all w-full flex items-center justify-center gap-2">
                       <i class="fa-solid fa-ban"></i> Suspend
                    </button>
                 <?php endif; ?>
               </form>
            </td>

          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php else: ?>
         <div class="p-12 text-center flex flex-col items-center">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-400">
               <i class="fa-solid fa-folder-open text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Reports Found</h3>
            <p class="text-gray-500">There are currently no reported posts to review.</p>
         </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- MEDIA MODAL -->
<div id="mediaModal" class="fixed inset-0 z-[100] bg-black/90 hidden flex items-center justify-center p-4 backdrop-blur-sm transition-opacity">
  <button onclick="closeMediaModal()" class="absolute top-4 right-4 md:top-6 md:right-6 text-white hover:text-orange-500 bg-white/10 hover:bg-white/20 w-10 h-10 rounded-full flex items-center justify-center transition-all z-10">
    <i class="fa-solid fa-xmark text-xl"></i>
  </button>
  <div class="max-w-4xl w-full max-h-[90vh] flex items-center justify-center relative">
    <div id="mediaModalContent" class="w-full flex justify-center"></div>
  </div>
</div>

<script>
function toggleDescription(id, btn) {
    const el = document.getElementById(id);
    if (el.classList.contains('line-clamp-2')) {
        el.classList.remove('line-clamp-2');
        btn.innerText = 'See Less';
    } else {
        el.classList.add('line-clamp-2');
        btn.innerText = 'See More';
    }
}

function openMediaModal(src, type) {
    const modal = document.getElementById('mediaModal');
    const content = document.getElementById('mediaModalContent');
    
    if (type === 'image') {
        content.innerHTML = `<img src="${src}" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl">`;
    } else if (type === 'video') {
        content.innerHTML = `<video src="${src}" controls autoplay class="max-w-full max-h-[85vh] rounded-lg shadow-2xl outline-none"></video>`;
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeMediaModal() {
    const modal = document.getElementById('mediaModal');
    const content = document.getElementById('mediaModalContent');
    modal.classList.add('hidden');
    content.innerHTML = '';
    document.body.style.overflow = '';
}

// Close modal on escape key or clicking outside
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeMediaModal();
});
document.getElementById('mediaModal').addEventListener('click', function(e) {
    if (e.target === this) closeMediaModal();
});
</script>

</body>
</html>
