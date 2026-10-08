<?php
session_start();
include("../connection.php");

// 1. Session check
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login");
    exit;
}

// Auto-migration: Create table if not exists
$con->query("SET SESSION sql_mode = ''");
$con->query("
    CREATE TABLE IF NOT EXISTS `tbl_anchor_applications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNIQUE NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(50) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `education` VARCHAR(255) NOT NULL,
        `photo` VARCHAR(255) NOT NULL,
        `aadhaar` VARCHAR(255) NOT NULL,
        `resume` VARCHAR(255) NOT NULL,
        `status` VARCHAR(50) DEFAULT 'Pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 2. Handle Action (Approve / Reject / Reset)
if (isset($_POST['action']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    if ($action === "approve") {
        $status = "Approved";
    } elseif ($action === "reject") {
        $status = "Rejected";
    } elseif ($action === "pending") {
        $status = "Pending";
    } else {
        $_SESSION['msg'] = "Invalid action specified!";
        header("Location: admin_anchor_applications.php");
        exit;
    }

    $q = mysqli_query($con, "UPDATE tbl_anchor_applications SET status='$status' WHERE id=$id");
    if ($q) {
        $_SESSION['msg'] = "Application status updated to '$status' successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Database error while updating status: " . mysqli_error($con);
        $_SESSION['msg_type'] = "error";
    }
    header("Location: admin_anchor_applications.php");
    exit;
}

// 3. Fetch applications
$applications_q = mysqli_query($con, "SELECT * FROM tbl_anchor_applications ORDER BY id DESC");
$apps = [];
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

if ($applications_q) {
    while ($row = mysqli_fetch_assoc($applications_q)) {
        $apps[] = $row;
        if ($row['status'] === 'Pending') $pending_count++;
        elseif ($row['status'] === 'Approved') $approved_count++;
        elseif ($row['status'] === 'Rejected') $rejected_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Sadhu Vandana - News Anchor Applications</title>
<!-- Tailwind CSS v4 -->
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen text-gray-800">

<!-- Header -->
<header class="bg-white shadow-md sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div class="flex items-center gap-3">
      <a href="index" class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
        <i class="fa-solid fa-arrow-left text-orange-600"></i>
      </a>
      <div>
        <h1 class="text-xl md:text-2xl font-bold text-gray-900">Anchor Applications</h1>
        <p class="text-xs text-gray-500">Review, approve, and manage applicants for news anchors</p>
      </div>
    </div>
    
    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
      <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-bold border border-yellow-200"><?= $pending_count ?> Pending</span>
      <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold border border-green-200"><?= $approved_count ?> Approved</span>
      <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold border border-red-200"><?= $rejected_count ?> Rejected</span>
      <input type="text" id="searchInput" placeholder="Search by name, phone, email..." class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 w-full md:w-64 mt-2 md:mt-0">
    </div>
  </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

  <!-- Message Toast -->
  <?php if(isset($_SESSION['msg'])): ?>
    <?php 
      $is_err = ($_SESSION['msg_type'] ?? '') === 'error';
      $bg_class = $is_err ? 'bg-red-100 text-red-800 border-red-200' : 'bg-green-100 text-green-800 border-green-200';
    ?>
    <div class="mb-6 p-4 rounded-xl border flex items-center gap-3 <?= $bg_class ?> shadow-sm">
      <i class="fa-solid <?= $is_err ? 'fa-triangle-exclamation' : 'fa-circle-check' ?> text-lg"></i>
      <span class="font-medium"><?= $_SESSION['msg']; unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?></span>
    </div>
  <?php endif; ?>

  <!-- Tab Filter Controls -->
  <div class="flex gap-2 border-b border-orange-200 pb-3 mb-6 overflow-x-auto">
    <button onclick="filterStatus('all')" class="status-tab-btn px-4 py-2 bg-orange-600 text-white font-bold text-sm rounded-lg shadow-sm transition" id="tab-all">All (<?= count($apps) ?>)</button>
    <button onclick="filterStatus('Pending')" class="status-tab-btn px-4 py-2 bg-white hover:bg-orange-50 border border-orange-200 text-orange-700 font-semibold text-sm rounded-lg transition" id="tab-Pending">Pending (<?= $pending_count ?>)</button>
    <button onclick="filterStatus('Approved')" class="status-tab-btn px-4 py-2 bg-white hover:bg-orange-50 border border-orange-200 text-orange-700 font-semibold text-sm rounded-lg transition" id="tab-Approved">Approved (<?= $approved_count ?>)</button>
    <button onclick="filterStatus('Rejected')" class="status-tab-btn px-4 py-2 bg-white hover:bg-orange-50 border border-orange-200 text-orange-700 font-semibold text-sm rounded-lg transition" id="tab-Rejected">Rejected (<?= $rejected_count ?>)</button>
  </div>

  <?php if (empty($apps)): ?>
    <div class="bg-white rounded-2xl shadow p-12 text-center border border-orange-100">
      <i class="fa-solid fa-address-card text-gray-300 text-6xl mb-4"></i>
      <h3 class="text-xl font-bold text-gray-700">No Applications Yet</h3>
      <p class="text-gray-500 mt-1">Anchor applications will show up here once users apply from the mobile app.</p>
    </div>
  <?php else: ?>

    <!-- Desktop Table View -->
    <div class="hidden md:block bg-white rounded-2xl shadow-lg border border-orange-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse" id="appsTable">
          <thead class="bg-gradient-to-r from-orange-500 to-orange-600 text-white text-xs uppercase tracking-wider font-bold">
            <tr>
              <th class="px-6 py-4">Photo</th>
              <th class="px-6 py-4">Applicant details</th>
              <th class="px-6 py-4">Education</th>
              <th class="px-6 py-4">Documents</th>
              <th class="px-6 py-4">Applied Date</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php foreach ($apps as $app): ?>
              <tr class="hover:bg-orange-50/50 transition duration-150 app-row" data-status="<?= htmlspecialchars($app['status']) ?>">
                
                <!-- Photo -->
                <td class="px-6 py-4">
                  <?php if (!empty($app['photo']) && file_exists("../uploads/anchor_applications/" . $app['photo'])): ?>
                    <img src="../uploads/anchor_applications/<?= htmlspecialchars($app['photo']) ?>" class="w-12 h-12 rounded-xl object-cover border border-orange-100 shadow-sm cursor-pointer hover:scale-105 transition-transform view-photo" alt="Photo"/>
                  <?php else: ?>
                    <div class="w-12 h-12 rounded-xl bg-orange-50 border border-orange-100 flex items-center justify-center text-orange-400">
                      <i class="fa-solid fa-user-tie text-lg"></i>
                    </div>
                  <?php endif; ?>
                </td>

                <!-- Applicant Details -->
                <td class="px-6 py-4">
                  <div class="font-bold text-gray-900"><?= htmlspecialchars($app['name']) ?></div>
                  <div class="text-xs text-gray-500 mt-0.5 flex flex-col gap-0.5">
                    <span class="hover:text-orange-600 cursor-pointer"><i class="fa-solid fa-phone w-4"></i><?= htmlspecialchars($app['phone']) ?></span>
                    <span class="hover:text-orange-600 cursor-pointer"><i class="fa-solid fa-envelope w-4"></i><?= htmlspecialchars($app['email']) ?></span>
                  </div>
                </td>

                <!-- Education -->
                <td class="px-6 py-4 font-medium text-gray-700">
                  <?= htmlspecialchars($app['education']) ?>
                </td>

                <!-- Documents -->
                <td class="px-6 py-4">
                  <div class="flex gap-2">
                    <?php if (!empty($app['aadhaar']) && file_exists("../uploads/anchor_applications/" . $app['aadhaar'])): ?>
                      <a href="../uploads/anchor_applications/<?= htmlspecialchars($app['aadhaar']) ?>" target="_blank" class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-bold border border-blue-100 flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-id-card"></i> Aadhaar
                      </a>
                    <?php else: ?>
                      <span class="text-xs text-gray-400">No Aadhaar</span>
                    <?php endif; ?>

                    <?php if (!empty($app['resume']) && file_exists("../uploads/anchor_applications/" . $app['resume'])): ?>
                      <a href="../uploads/anchor_applications/<?= htmlspecialchars($app['resume']) ?>" target="_blank" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold border border-indigo-100 flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-file-invoice"></i> Resume
                      </a>
                    <?php else: ?>
                      <span class="text-xs text-gray-400">No Resume</span>
                    <?php endif; ?>
                  </div>
                </td>

                <!-- Date -->
                <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                  <?= date("d M Y h:i A", strtotime($app['created_at'])) ?>
                </td>

                <!-- Status -->
                <td class="px-6 py-4">
                  <?php 
                    $status = $app['status'];
                    if ($status === 'Approved') {
                        $badge = 'bg-green-50 text-green-700 border-green-200';
                    } elseif ($status === 'Rejected') {
                        $badge = 'bg-red-50 text-red-700 border-red-200';
                    } else {
                        $badge = 'bg-yellow-50 text-yellow-700 border-yellow-200';
                    }
                  ?>
                  <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $badge ?>"><?= $status ?></span>
                </td>

                <!-- Actions -->
                <td class="px-6 py-4">
                  <form method="post" class="flex justify-center gap-2">
                    <input type="hidden" name="id" value="<?= $app['id'] ?>">
                    
                    <?php if ($status !== 'Approved'): ?>
                      <button type="submit" name="action" value="approve" class="w-9 h-9 bg-green-50 hover:bg-green-100 text-green-600 rounded-xl border border-green-200 flex items-center justify-center transition shadow-sm" title="Approve">
                        <i class="fa-solid fa-check"></i>
                      </button>
                    <?php endif; ?>

                    <?php if ($status !== 'Rejected'): ?>
                      <button type="submit" name="action" value="reject" class="w-9 h-9 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl border border-red-200 flex items-center justify-center transition shadow-sm" title="Reject">
                        <i class="fa-solid fa-ban"></i>
                      </button>
                    <?php endif; ?>

                    <?php if ($status !== 'Pending'): ?>
                      <button type="submit" name="action" value="pending" class="w-9 h-9 bg-yellow-50 hover:bg-yellow-100 text-yellow-600 rounded-xl border border-yellow-200 flex items-center justify-center transition shadow-sm" title="Reset to Pending">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                      </button>
                    <?php endif; ?>
                  </form>
                </td>

              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Mobile Cards View -->
    <div class="md:hidden space-y-4" id="appsCards">
      <?php foreach ($apps as $app): ?>
        <?php 
          $status = $app['status'];
          if ($status === 'Approved') {
              $badge = 'bg-green-50 text-green-700 border-green-200';
          } elseif ($status === 'Rejected') {
              $badge = 'bg-red-50 text-red-700 border-red-200';
          } else {
              $badge = 'bg-yellow-50 text-yellow-700 border-yellow-200';
          }
        ?>
        <div class="bg-white rounded-2xl shadow border border-orange-100 p-5 app-card transition" data-status="<?= htmlspecialchars($app['status']) ?>">
          
          <div class="flex justify-between items-start gap-3 mb-4">
            <div class="flex items-center gap-3">
              <?php if (!empty($app['photo']) && file_exists("../uploads/anchor_applications/" . $app['photo'])): ?>
                <img src="../uploads/anchor_applications/<?= htmlspecialchars($app['photo']) ?>" class="w-14 h-14 rounded-xl object-cover border border-orange-100 shadow-sm view-photo" alt="Photo"/>
              <?php else: ?>
                <div class="w-14 h-14 rounded-xl bg-orange-50 border border-orange-100 flex items-center justify-center text-orange-400">
                  <i class="fa-solid fa-user-tie text-2xl"></i>
                </div>
              <?php endif; ?>
              <div>
                <h3 class="font-bold text-gray-900 text-base leading-snug"><?= htmlspecialchars($app['name']) ?></h3>
                <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($app['education']) ?></p>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xxs font-bold border <?= $badge ?>"><?= $status ?></span>
          </div>

          <div class="space-y-2 border-t border-gray-100 pt-3 mb-4">
            <div class="flex items-center gap-2 text-xs text-gray-600">
              <i class="fa-solid fa-phone text-orange-500 w-4"></i>
              <span><?= htmlspecialchars($app['phone']) ?></span>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-600">
              <i class="fa-solid fa-envelope text-orange-500 w-4"></i>
              <span><?= htmlspecialchars($app['email']) ?></span>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-600">
              <i class="fa-solid fa-calendar-day text-orange-500 w-4"></i>
              <span><?= date("d M Y", strtotime($app['created_at'])) ?></span>
            </div>
          </div>

          <div class="flex gap-2 mb-4 bg-orange-50/50 p-2.5 rounded-xl border border-orange-100/50">
            <?php if (!empty($app['aadhaar']) && file_exists("../uploads/anchor_applications/" . $app['aadhaar'])): ?>
              <a href="../uploads/anchor_applications/<?= htmlspecialchars($app['aadhaar']) ?>" target="_blank" class="flex-1 text-center py-2 bg-white hover:bg-orange-50 border border-orange-200 text-orange-700 font-bold rounded-lg text-xs transition">
                <i class="fa-solid fa-id-card mr-1"></i> Aadhaar
              </a>
            <?php endif; ?>
            <?php if (!empty($app['resume']) && file_exists("../uploads/anchor_applications/" . $app['resume'])): ?>
              <a href="../uploads/anchor_applications/<?= htmlspecialchars($app['resume']) ?>" target="_blank" class="flex-1 text-center py-2 bg-white hover:bg-orange-50 border border-orange-200 text-orange-700 font-bold rounded-lg text-xs transition">
                <i class="fa-solid fa-file-invoice mr-1"></i> Resume
              </a>
            <?php endif; ?>
          </div>

          <form method="post" class="grid grid-cols-3 gap-2">
            <input type="hidden" name="id" value="<?= $app['id'] ?>">

            <button type="submit" name="action" value="approve" class="py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center justify-center gap-1.5 <?= $status === 'Approved' ? 'opacity-30 cursor-not-allowed' : '' ?>" <?= $status === 'Approved' ? 'disabled' : '' ?>>
              <i class="fa-solid fa-check"></i> Approve
            </button>

            <button type="submit" name="action" value="reject" class="py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center justify-center gap-1.5 <?= $status === 'Rejected' ? 'opacity-30 cursor-not-allowed' : '' ?>" <?= $status === 'Rejected' ? 'disabled' : '' ?>>
              <i class="fa-solid fa-ban"></i> Reject
            </button>

            <button type="submit" name="action" value="pending" class="py-2.5 bg-gray-500 hover:bg-gray-600 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center justify-center gap-1.5 <?= $status === 'Pending' ? 'opacity-30 cursor-not-allowed' : '' ?>" <?= $status === 'Pending' ? 'disabled' : '' ?>>
              <i class="fa-solid fa-rotate-left"></i> Reset
            </button>
          </form>

        </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

</main>

<!-- Photo Lightbox Modal -->
<div id="photoModal" class="fixed inset-0 bg-black/85 hidden items-center justify-center z-50">
  <span id="closePhotoModal" class="absolute top-5 right-5 text-white text-4xl cursor-pointer hover:text-orange-500 transition">&times;</span>
  <img id="modalUserPhoto" class="max-h-[85vh] max-w-[90vw] rounded-xl shadow-2xl border-2 border-white/10" src=""/>
</div>

<script>
// 1. Photo lightbox viewer logic
document.querySelectorAll(".view-photo").forEach(img => {
    img.onclick = () => {
        document.getElementById("modalUserPhoto").src = img.src;
        document.getElementById("photoModal").classList.remove("hidden");
        document.getElementById("photoModal").classList.add("flex");
    };
});

const closePhoto = () => {
    document.getElementById("photoModal").classList.add("hidden");
    document.getElementById("photoModal").classList.remove("flex");
};

document.getElementById("closePhotoModal").onclick = closePhoto;
document.getElementById("photoModal").onclick = e => {
    if(e.target === document.getElementById("photoModal")){
        closePhoto();
    }
};

// 2. Tab Filter State
let currentTabFilter = 'all';

function filterStatus(status) {
    currentTabFilter = status;
    
    // Toggle active tab buttons colors
    document.querySelectorAll(".status-tab-btn").forEach(btn => {
        btn.className = "status-tab-btn px-4 py-2 bg-white hover:bg-orange-50 border border-orange-200 text-orange-700 font-semibold text-sm rounded-lg transition";
    });
    
    const activeBtn = document.getElementById("tab-" + status);
    if(activeBtn) {
        activeBtn.className = "status-tab-btn px-4 py-2 bg-orange-600 text-white font-bold text-sm rounded-lg shadow-sm transition";
    }
    
    applyFilters();
}

// 3. Search and status filters application
const searchInput = document.getElementById('searchInput');

function applyFilters() {
    const searchVal = searchInput ? searchInput.value.toLowerCase() : '';
    
    // Desktop row filters
    document.querySelectorAll('#appsTable tbody .app-row').forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        const matchesStatus = (currentTabFilter === 'all' || rowStatus === currentTabFilter);
        const matchesSearch = row.innerText.toLowerCase().includes(searchVal);
        
        row.style.display = (matchesStatus && matchesSearch) ? '' : 'none';
    });

    // Mobile card filters
    document.querySelectorAll('#appsCards .app-card').forEach(card => {
        const cardStatus = card.getAttribute('data-status');
        const matchesStatus = (currentTabFilter === 'all' || cardStatus === currentTabFilter);
        const matchesSearch = card.innerText.toLowerCase().includes(searchVal);
        
        card.style.display = (matchesStatus && matchesSearch) ? '' : 'none';
    });
}

if(searchInput) {
    searchInput.addEventListener('input', applyFilters);
}
</script>

</body>
</html>
