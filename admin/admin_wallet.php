<?php
include("../connection.php");
//session_start();

/* ---- Fetch All Wallet Transactions ---- */
$q = $con->query("
  SELECT w.*, m.name, m.mobile, m.profile_photo
  FROM tbl_wallet w
  LEFT JOIN tbl_members m ON w.user_id = m.id
  ORDER BY w.date DESC
");

$transactions = [];
while($row = $q->fetch_assoc()){
    $transactions[] = [
        'id' => $row['id'],
        'txn' => "#TXN" . str_pad($row['id'],3,'0',STR_PAD_LEFT),
        'name' => $row['name'] ?: 'Unknown',
        'phone' => $row['mobile'] ?: '---',
        'profile' => $row['profile_photo'],
        'amount' => $row['payment_ammount'],
        'payment_id' => $row['payment_id'],
        'date' => date("d M Y, h:i A", strtotime($row['date'])),
        'method' => "Online",
        'status' => ucfirst($row['status'])
    ];
}

/* ---- SUM of SUCCESS ONLY ---- */
$sum = $con->query("SELECT SUM(payment_ammount) FROM tbl_wallet WHERE status='success'")->fetch_row()[0] ?: 0;

/* ---- TOTAL SUCCESS DONORS ---- */
$donors = $con->query("SELECT COUNT(DISTINCT user_id) FROM tbl_wallet WHERE status='success'")->fetch_row()[0];

/* ---- THIS MONTH SUCCESS ---- */
$month = date("m");
$year = date("Y");
$monthSum = $con->query("SELECT SUM(payment_ammount) FROM tbl_wallet WHERE status='success' AND MONTH(date)=$month AND YEAR(date)=$year")->fetch_row()[0] ?: 0;
?>
<?php include("header.php"); ?>

<!-- MAIN -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">



  <!-- STATS CARDS -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-md p-3 border border-gray-100">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
          <i class="fa-solid fa-wallet text-blue-600 text-xl"></i>
        </div>
        <div>
          <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Total Amount</p>
          <h3 class="text-lg font-black text-gray-800">₹<?= $sum ?></h3>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-3 border border-gray-100">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
          <i class="fa-solid fa-users text-green-600 text-xl"></i>
        </div>
        <div>
          <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Total Donors</p>
          <h3 class="text-lg font-black text-gray-800"><?= $donors ?></h3>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-3 border border-gray-100">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
          <i class="fa-solid fa-calendar text-orange-600 text-xl"></i>
        </div>
        <div>
          <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">This Month</p>
          <h3 class="text-lg font-black text-gray-800">₹<?= $monthSum ?></h3>
        </div>
      </div>
    </div>
  </div>

  <!-- LIST VIEW (Desktop Table) -->
  <div class="hidden md:block bg-white rounded-xl shadow border border-gray-100 overflow-hidden mb-4">
    
    <div class="p-2 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50">
      <div class="flex items-center gap-3">
        <a href="index.php" class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
          <i class="fa-solid fa-arrow-left text-orange-600 text-sm"></i>
        </a>
        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
          <i class="fa-solid fa-wallet text-orange-600"></i>
          Wallet & Earnings
        </h2>
      </div>
      
      <div class="flex items-center gap-2 w-full sm:w-auto">
        <div class="relative w-full sm:w-64">
          <i class="fa-solid fa-search absolute left-3 top-2.5 text-orange-400 text-sm"></i>
          <input 
            type="search" 
            id="searchInput" 
            placeholder="Search name, phone, or TXN..." 
            class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-200 shadow-sm rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition text-sm text-gray-700"
          >
        </div>
        <div class="text-sm font-medium text-gray-500 bg-white px-3 py-1.5 rounded-lg border shadow-sm whitespace-nowrap">
          Total: <span class="text-orange-600 font-bold"><?= count($transactions) ?></span>
        </div>
      </div>
    </div>

    <div class="overflow-y-auto max-h-[525px]" id="tableBodyDesktop">
      <table class="w-full text-sm border-t border-gray-100">
        <thead class="bg-orange-500 text-white sticky top-0 z-10">
          <tr>
            <th class="px-4 py-2 text-left font-medium">#</th>
            <th class="px-4 py-2 text-left font-medium">Donor Name</th>
            <th class="px-4 py-2 text-left font-medium">Phone</th>
            <th class="px-4 py-2 text-left font-medium">Transaction ID</th>
            <th class="px-4 py-2 text-left font-medium">Amount</th>
            <th class="px-4 py-2 text-left font-medium">Date & Time</th>
            <th class="px-4 py-2 text-center font-medium">Payment</th>
            <th class="px-4 py-2 text-center font-medium">Status</th>
          </tr>
        </thead>

        <tbody id="searchDataDesktop" class="divide-y divide-gray-200">
          <?php foreach($transactions as $t): ?>
          <tr class="hover:bg-orange-50 transition search-row">
            <td class="px-4 py-2 text-sm"><?= $t['txn'] ?></td>

            <td class="px-4 py-2 text-sm">
              <div class="flex items-center gap-3">

                <?php if(!empty($t['profile']) && file_exists("../uploads/photo/".$t['profile'])): ?>
                 <img 
  src="../uploads/photo/<?= $t['profile'] ?>" 
  class="w-8 h-8 rounded-full border-2 border-orange-400 shadow cursor-pointer"
  onclick="openImageModal('../uploads/photo/<?= $t['profile'] ?>')"
/>

                <?php else: ?>
                  <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">
                    <?= strtoupper(substr($t['name'],0,1)) ?>
                  </div>
                <?php endif; ?>

                <span class="font-semibold"><?= $t['name'] ?></span>
              </div>
            </td>

            <td class="px-4 py-2 text-sm"><?= $t['phone'] ?></td>
            <td class="px-4 py-2 text-sm"><?= $t['payment_id'] ?></td>
            <td class="px-4 py-2 text-sm text-green-600 font-bold">₹<?= $t['amount'] ?></td>
            <td class="px-4 py-2 text-sm"><?= $t['date'] ?></td>

            <td class="px-4 py-2 text-center">
              <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">Online</span>
            </td>

            <td class="px-4 py-2 text-center">
              <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium"><?= $t['status'] ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>

      </table>

    </div>
  </div>

  <!-- MOBILE VIEW -->
  <div class="md:hidden space-y-4" id="searchDataMobile">
    <!-- MOBILE SEARCH INPUT (Visible only on mobile, same ID won't work so we use class searchInputMob) -->
    <div class="relative w-full mb-4">
      <i class="fa-solid fa-search absolute left-3 top-2.5 text-orange-400 text-sm"></i>
      <input 
        type="search" 
        id="searchInputMob" 
        placeholder="Search name, phone, or TXN..." 
        class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 shadow-sm rounded-lg focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition text-sm text-gray-700"
      >
    </div>

    <?php foreach($transactions as $t): ?>
    <div class="bg-white p-4 rounded-xl shadow border border-gray-100 search-row-mob">

      <div class="flex items-start justify-between">
        <div class="flex items-center gap-3">

          <?php if(!empty($t['profile']) && file_exists("../uploads/photo/".$t['profile'])): ?>
            <img 
  src="../uploads/photo/<?= $t['profile'] ?>" 
  class="w-8 h-8 rounded-full border-2 border-orange-400 shadow cursor-pointer"
  onclick="openImageModal('../uploads/photo/<?= $t['profile'] ?>')"
/>

          <?php else: ?>
            <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">
              <?= strtoupper(substr($t['name'],0,1)) ?>
            </div>
          <?php endif; ?>

          <div>
            <h3 class="font-bold text-gray-800"><?= $t['name'] ?></h3>
            <p class="text-xs text-gray-500"><?= $t['txn'] ?></p>
          </div>

        </div>

        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs"><?= $t['status'] ?></span>
      </div>

      <div class="mt-3 space-y-2">
        <div class="flex justify-between">
          <span>Amount:</span>
          <span class="font-bold text-green-600">₹<?= $t['amount'] ?></span>
        </div>

        <div class="text-sm text-gray-600"><?= $t['phone'] ?></div>
        <div class="text-sm text-gray-600"><?= $t['date'] ?></div>
        <div class="text-sm text-gray-600"><?= $t['method'] ?></div>
      </div>

    </div>
    <?php endforeach; ?>
  </div>
<!-- IMAGE VIEW MODAL -->
<div id="imageModal" 
     class="fixed inset-0 bg-black bg-opacity-70 hidden items-center justify-center z-50 backdrop-blur-sm">

  <div class="relative max-w-[90%] max-h-[90%]">
    
    <!-- Close Button -->
    <button class="absolute -top-4 -right-4 bg-white text-black rounded-full w-10 h-10 shadow-lg text-2xl flex items-center justify-center"
            onclick="closeImageModal()">
      &times;
    </button>

    <!-- Modal Image -->
    <img id="modalImage" src="" 
         class="rounded-xl shadow-2xl border-4 border-white object-contain max-h-[90vh] max-w-full"/>

  </div>

</div>

<script>
function openImageModal(src) {
  document.getElementById('modalImage').src = src;
  document.getElementById('imageModal').classList.remove('hidden');
  document.getElementById('imageModal').classList.add('flex');
}

function closeImageModal() {
  document.getElementById('imageModal').classList.add('hidden');
  document.getElementById('imageModal').classList.remove('flex');
}

// Close when clicking outside
document.getElementById('imageModal').addEventListener('click', function(e){
  if(e.target === this) closeImageModal();
});
</script>

</main>

<!-- SEARCH SCRIPT -->
<script>
// Desktop Search
document.getElementById("searchInput").addEventListener("keyup", function () {
  var value = this.value.toLowerCase();
  document.querySelectorAll("#searchDataDesktop .search-row").forEach(row => {
    row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
  });
});

// Mobile Search
document.getElementById("searchInputMob").addEventListener("keyup", function () {
  var value = this.value.toLowerCase();
  document.querySelectorAll(".search-row-mob").forEach(row => {
    row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
  });
});
</script>

</body>
</html>
