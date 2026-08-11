<?php
session_start();
include("../connection.php");

$page_limit = 20; // Kitne members ek baar mein load karne hain


// Handle Unblock
if(isset($_POST['action']) && isset($_POST['id'])){
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    if($action === "unblock"){
        $status = "Approved";
        $q = mysqli_query($con, "UPDATE tbl_members SET status='$status' WHERE id=$id");
        if($q){
            $_SESSION['msg'] = "Member unblocked successfully!";
        } else {
            $_SESSION['msg'] = "Database error!";
        }
    }
    header("Location: admin_block_user.php");
    exit;
}

// Handle AJAX Request for Infinite Scroll
if(isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $limit = $page_limit;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    
    $search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';
    
    $whereClause = "status='Blocked'";
    if($search !== '') {
        $whereClause .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR mobile LIKE '%$search%' OR cast LIKE '%$search%')";
    }
    
    $total_search_query = mysqli_query($con, "SELECT COUNT(*) as count FROM tbl_members WHERE $whereClause");
    $total_search_row = mysqli_fetch_assoc($total_search_query);
    $total_members = $total_search_row['count'];
    
    $query = "SELECT * FROM tbl_members WHERE $whereClause ORDER BY date DESC LIMIT $limit OFFSET $offset";
    $members_ajax = mysqli_query($con, $query);
    
    $desktop_html = '';
    $mobile_html = '';
    $a = $offset + 1;
    
    while($member = mysqli_fetch_assoc($members_ajax)){
        // Build Desktop HTML
        $photo_html = '';
        if($member['profile_photo'] && file_exists("../uploads/photo/".$member['profile_photo'])){
            $photo_html = '<img src="../uploads/photo/'.$member['profile_photo'].'" class="w-10 h-10 rounded-full object-cover cursor-pointer view-photo" />';
        } else {
            $photo_html = '<div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold text-sm">'.strtoupper(substr($member['name'],0,2)).'</div>';
        }

        $desktop_html .= '<tr class="hover:bg-orange-50 transition">
          <td class="px-6 py-4">'.$a.'</td>
          <td class="px-6 py-4 flex items-center gap-3">
            '.$photo_html.'
            '.htmlspecialchars($member['name']).'
          </td>
          <td class="px-6 py-4">'.htmlspecialchars($member['email']).'</td>
          <td class="px-6 py-4">'.htmlspecialchars($member['mobile']).'</td>
          <td class="px-6 py-4">'.htmlspecialchars($member['cast']).'</td>
          <td class="px-6 py-4">'.date("d M Y", strtotime($member['date'])).'</td>
          <td class="px-6 py-4">
            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Blocked</span>
          </td>
          <td class="px-6 py-4 text-center">
            <form method="post">
              <input type="hidden" name="id" value="'.$member['id'].'">
              <button type="submit" name="action" value="unblock" class="w-8 h-8 bg-green-100 hover:bg-green-200 text-green-600 rounded-lg transition" title="Unblock">
                  <i class="fa-solid fa-check text-sm"></i>
              </button>
            </form>
          </td>
        </tr>';
        
        // Build Mobile HTML
        $m_photo_html = '';
        if($member['profile_photo'] && file_exists("../uploads/photo/".$member['profile_photo'])){
            $m_photo_html = '<img src="../uploads/photo/'.$member['profile_photo'].'" class="w-12 h-12 rounded-full object-cover cursor-pointer view-photo"/>';
        } else {
            $m_photo_html = '<div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold text-sm">'.strtoupper(substr($member['name'],0,2)).'</div>';
        }

        $mobile_html .= '<div class="bg-white rounded-xl shadow-lg p-4 mobile-card">
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
              '.$m_photo_html.'
              <div>
                <h3 class="text-base font-bold text-gray-800">'.htmlspecialchars($member['name']).'</h3>
                <p class="text-xs text-gray-500">#'.$a.'</p>
              </div>
            </div>
            <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Blocked</span>
          </div>
          <div class="space-y-2 mb-4">
            <div class="flex items-center gap-2 text-sm text-gray-600">
              <i class="fa-solid fa-envelope text-orange-500 w-4"></i>
              <span>'.htmlspecialchars($member['email']).'</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-600">
              <i class="fa-solid fa-phone text-orange-500 w-4"></i>
              <span>'.htmlspecialchars($member['mobile']).'</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-600">
              <i class="fa-solid fa-branch-code text-orange-500 w-4"></i>
              <span>'.htmlspecialchars($member['cast']).'</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-600">
              <i class="fa-solid fa-calendar text-orange-500 w-4"></i>
              <span>'.date("d M Y", strtotime($member['date'])).'</span>
            </div>
          </div>
          <form method="post">
            <input type="hidden" name="id" value="'.$member['id'].'">
            <button type="submit" name="action" value="unblock" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-green-100 hover:bg-green-200 text-green-600 rounded-lg transition w-full">
              <i class="fa-solid fa-check text-lg"></i>
              <span class="text-sm font-medium">Unblock Member</span>
            </button>
          </form>
        </div>';
        
        $a++;
    }
    
    echo json_encode(['desktop' => $desktop_html, 'mobile' => $mobile_html, 'total' => $total_members]);
    exit;
}

// Fetch total count for display
$total_query = mysqli_query($con, "SELECT COUNT(*) as count FROM tbl_members WHERE status='Blocked'");
$total_row = mysqli_fetch_assoc($total_query);
$total_members = $total_row['count'];

// Initial Load
$members = mysqli_query($con, "SELECT * FROM tbl_members WHERE status='Blocked' ORDER BY date DESC LIMIT $page_limit");
?>

<?php include("header.php"); ?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">

<?php if(isset($_SESSION['msg'])): ?>
<div class="mb-2 p-1 bg-green-100 text-green-800 rounded text-sm text-center font-semibold border border-green-300">
  <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
</div>
<?php endif; ?>

<!-- Desktop Table -->
<div class="hidden md:block bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
  
  <div class="p-2 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50">
    <div class="flex items-center gap-3">
      <a href="index.php" class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
        <i class="fa-solid fa-arrow-left text-orange-600 text-sm"></i>
      </a>
      <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
        Blocked Members
      </h2>
    </div>
    
    <div class="flex items-center gap-2 w-full sm:w-auto">
      <div class="relative w-full sm:w-64">
        <i class="fa-solid fa-search absolute left-3 top-2.5 text-orange-400 text-sm"></i>
        <input 
          type="text" 
          id="searchInput" 
          placeholder="Search by name, mobile..." 
          class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 shadow-sm rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition text-sm text-gray-700"
        >
      </div>
      <div class="text-sm font-medium text-gray-500 bg-white px-3 py-1.5 rounded-lg border shadow-sm whitespace-nowrap">
        Blocked: <span id="totalMembersCount" class="text-red-600 font-bold"><?= $total_members ?></span>
      </div>
    </div>
  </div>

  <div class="table-container overflow-y-auto" style="max-height: calc(100vh - 130px);" id="tableScrollContainer">
    <table class="w-full text-sm">
      <thead class="bg-gradient-to-r from-orange-500 to-orange-600 text-white sticky top-0 z-10">
        <tr>
          <th class="px-6 py-4 text-left">#</th>
          <th class="px-6 py-4 text-left">Name</th>
          <th class="px-6 py-4 text-left">Email</th>
          <th class="px-6 py-4 text-left">Phone</th>
          <th class="px-6 py-4 text-left">Community</th>
          <th class="px-6 py-4 text-left">Date</th>
          <th class="px-6 py-4 text-left">Status</th>
          <th class="px-6 py-4 text-center">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200" id="tableBody">
      <?php $a=1; while($member = mysqli_fetch_assoc($members)): ?>
        <tr class="hover:bg-orange-50 transition">
          <td class="px-6 py-4"><?= $a; ?></td>
          <td class="px-6 py-4 flex items-center gap-3">
            <?php if($member['profile_photo'] && file_exists("../uploads/photo/".$member['profile_photo'])): ?>
              <img src="../uploads/photo/<?= $member['profile_photo'] ?>" class="w-10 h-10 rounded-full object-cover cursor-pointer view-photo" />
            <?php else: ?>
              <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold text-sm"><?= strtoupper(substr($member['name'],0,2)) ?></div>
            <?php endif; ?>
            <?= htmlspecialchars($member['name']) ?>
          </td>
          <td class="px-6 py-4"><?= htmlspecialchars($member['email']) ?></td>
          <td class="px-6 py-4"><?= htmlspecialchars($member['mobile']) ?></td>
          <td class="px-6 py-4"><?= htmlspecialchars($member['cast']) ?></td>
          <td class="px-6 py-4"><?= date("d M Y", strtotime($member['date'])) ?></td>
          <td class="px-6 py-4">
            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Blocked</span>
          </td>
          <td class="px-6 py-4 text-center">
            <form method="post">
              <input type="hidden" name="id" value="<?= $member['id'] ?>">
              <button type="submit" name="action" value="unblock" class="w-8 h-8 bg-green-100 hover:bg-green-200 text-green-600 rounded-lg transition" title="Unblock">
                  <i class="fa-solid fa-check text-sm"></i>
              </button>
            </form>
          </td>
        </tr>
      <?php $a++; endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Mobile Card View -->
<div class="md:hidden space-y-4" id="mobileCards">
<?php
$a=1;
// Initial load for mobile view
$members = mysqli_query($con, "SELECT * FROM tbl_members WHERE status='Blocked' ORDER BY date DESC LIMIT $page_limit");
while($member = mysqli_fetch_assoc($members)):
?>
<div class="bg-white rounded-xl shadow-lg p-4 mobile-card">
  <div class="flex items-start justify-between mb-3">
    <div class="flex items-center gap-3">
      <?php if($member['profile_photo'] && file_exists("../uploads/photo/".$member['profile_photo'])): ?>
        <img src="../uploads/photo/<?= $member['profile_photo'] ?>" class="w-12 h-12 rounded-full object-cover cursor-pointer view-photo"/>
      <?php else: ?>
        <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold text-sm"><?= strtoupper(substr($member['name'],0,2)) ?></div>
      <?php endif; ?>
      <div>
        <h3 class="text-base font-bold text-gray-800"><?= htmlspecialchars($member['name']) ?></h3>
        <p class="text-xs text-gray-500">#<?= $a; ?></p>
      </div>
    </div>
    <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Blocked</span>
  </div>
  <div class="space-y-2 mb-4">
    <div class="flex items-center gap-2 text-sm text-gray-600">
      <i class="fa-solid fa-envelope text-orange-500 w-4"></i>
      <span><?= htmlspecialchars($member['email']) ?></span>
    </div>
    <div class="flex items-center gap-2 text-sm text-gray-600">
      <i class="fa-solid fa-phone text-orange-500 w-4"></i>
      <span><?= htmlspecialchars($member['mobile']) ?></span>
    </div>
    <div class="flex items-center gap-2 text-sm text-gray-600">
      <i class="fa-solid fa-branch-code text-orange-500 w-4"></i>
      <span><?= htmlspecialchars($member['cast']) ?></span>
    </div>
    <div class="flex items-center gap-2 text-sm text-gray-600">
      <i class="fa-solid fa-calendar text-orange-500 w-4"></i>
      <span><?= date("d M Y", strtotime($member['date'])) ?></span>
    </div>
  </div>
  <form method="post">
    <input type="hidden" name="id" value="<?= $member['id'] ?>">
    <button type="submit" name="action" value="unblock" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-green-100 hover:bg-green-200 text-green-600 rounded-lg transition">
      <i class="fa-solid fa-check text-lg"></i>
      <span class="text-sm font-medium">Unblock Member</span>
    </button>
  </form>
</div>
<?php $a++; endwhile; ?>
</div>

<!-- Image Modal -->
<div id="photoModal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center z-50">
    <span id="closePhotoModal" class="absolute top-5 right-5 text-white text-3xl cursor-pointer">&times;</span>
    <img id="modalUserPhoto" class="max-h-full max-w-full rounded-lg shadow-lg" />
</div>

<script>
// Event Delegation for Image modal
document.body.addEventListener('click', (e) => {
    if (e.target.classList.contains('view-photo')) {
        document.getElementById("modalUserPhoto").src = e.target.src;
        document.getElementById("photoModal").classList.remove("hidden");
        document.getElementById("photoModal").classList.add("flex");
    }
});

document.getElementById("closePhotoModal").onclick = () => {
    document.getElementById("photoModal").classList.add("hidden");
    document.getElementById("photoModal").classList.remove("flex");
};
document.getElementById("photoModal").onclick = (e) => {
    if(e.target === document.getElementById("photoModal")){
        document.getElementById("photoModal").classList.add("hidden");
        document.getElementById("photoModal").classList.remove("flex");
    }
};

// Server-side Live search & Infinite Scroll Logic
let currentPage = 1;
let loading = false;
let hasMore = true;
let currentSearch = '';

const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
      currentSearch = searchInput.value.trim();
      currentPage = 1;
      hasMore = true;
      document.querySelector('#tableBody').innerHTML = '';
      document.getElementById('mobileCards').innerHTML = '';
      loadMore(true);
  }
});

const tableContainer = document.getElementById('tableScrollContainer');

if (tableContainer) {
    tableContainer.addEventListener('scroll', () => {
        if (Math.ceil(tableContainer.scrollTop + tableContainer.clientHeight) >= tableContainer.scrollHeight - 150) {
            loadMore();
        }
    });
}

window.addEventListener('scroll', () => {
    if (window.innerWidth < 768) { 
        const scrollY = window.scrollY || document.documentElement.scrollTop;
        if (Math.ceil(window.innerHeight + scrollY) >= document.documentElement.scrollHeight - 200) {
            loadMore();
        }
    }
});

function loadMore(isSearch = false) {
    if (loading || (!hasMore && !isSearch)) return;
    loading = true;
    
    if (!isSearch) {
        currentPage++;
    }
    
    fetch(`admin_block_user.php?ajax=1&page=${currentPage}&search=${encodeURIComponent(currentSearch)}`)
        .then(res => res.json())
        .then(data => {
            if(data.desktop.trim() === '' && data.mobile.trim() === '') {
                hasMore = false;
            } else {
                document.querySelector('#tableBody').insertAdjacentHTML('beforeend', data.desktop);
                document.getElementById('mobileCards').insertAdjacentHTML('beforeend', data.mobile);
            }
            
            if (data.total !== undefined) {
                document.getElementById('totalMembersCount').innerText = data.total;
            }
            
            loading = false;
            
            // Auto-load if no scrollbar
            if (tableContainer && tableContainer.scrollHeight <= tableContainer.clientHeight && hasMore) {
                loadMore();
            }
        })
        .catch(err => {
            console.error(err);
            loading = false;
        });
}
</script>

</body>
</html>
