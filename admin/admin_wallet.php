<?php
include("../connection.php");
session_start();

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
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Sadhu Vandana - Wallet Transactions</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>

<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen">

<!-- HEADER -->
<header class="bg-white shadow-md sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">

    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center gap-3">
        <a href="index" class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition">
          <i class="fa-solid fa-arrow-left text-orange-600"></i>
        </a>
        <div>
          <h1 class="text-xl md:text-2xl font-bold text-gray-800">Total Earnings</h1>
          <p class="text-xs text-gray-500">View all donations and contributions</p>
        </div>
      </div>

      <span class="px-3 py-1.5 bg-blue-100 text-blue-600 rounded-full text-sm font-semibold">
        ₹<?= $sum ?>
      </span>
    </div>

    <!-- 🔍 SEARCH BAR HERE -->
    <div class="mt-2">
      <input 
        type="text" 
        id="searchInput" 
        placeholder="Search by name, phone or transaction ID..." 
        class="w-full px-4 py-2 bg-orange-50 border border-orange-200 rounded-lg focus:ring-2 focus:ring-orange-400 outline-none"
      >
    </div>

  </div>
</header>

<!-- MAIN -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

  <!-- STATS CARDS -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-md p-4">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
          <i class="fa-solid fa-wallet text-blue-600 text-xl"></i>
        </div>
        <div>
          <p class="text-xs text-gray-500">Total Amount</p>
          <h3 class="text-xl font-bold text-gray-800">₹<?= $sum ?></h3>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-4">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
          <i class="fa-solid fa-users text-green-600 text-xl"></i>
        </div>
        <div>
          <p class="text-xs text-gray-500">Total Donors</p>
          <h3 class="text-xl font-bold text-gray-800"><?= $donors ?></h3>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-4">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
          <i class="fa-solid fa-calendar text-orange-600 text-xl"></i>
        </div>
        <div>
          <p class="text-xs text-gray-500">This Month</p>
          <h3 class="text-xl font-bold text-gray-800">₹<?= $monthSum ?></h3>
        </div>
      </div>
    </div>
  </div>

  <!-- TABLE DESKTOP -->
  <div class="hidden md:block bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="max-h-[calc(100vh-350px)] overflow-y-auto" id="tableBodyDesktop">

      <table class="w-full">
        <thead class="bg-gradient-to-r from-orange-500 to-orange-600 text-white sticky top-0 z-10">
          <tr>
            <th class="px-6 py-4">#</th>
            <th class="px-6 py-4">Donor Name</th>
            <th class="px-6 py-4">Phone</th>
            <th class="px-6 py-4">Transaction ID</th>
            <th class="px-6 py-4">Amount</th>
            <th class="px-6 py-4">Date & Time</th>
            <th class="px-6 py-4">Payment</th>
            <th class="px-6 py-4">Status</th>
          </tr>
        </thead>

        <tbody id="searchDataDesktop" class="divide-y divide-gray-200">
          <?php foreach($transactions as $t): ?>
          <tr class="hover:bg-orange-50 transition search-row">
            <td class="px-6 py-4"><?= $t['txn'] ?></td>

            <td class="px-6 py-4">
              <div class="flex items-center gap-3">

                <?php if(!empty($t['profile']) && file_exists("../uploads/photo/".$t['profile'])): ?>
                 <img 
  src="../uploads/photo/<?= $t['profile'] ?>" 
  class="w-12 h-12 rounded-full border-2 border-orange-400 shadow cursor-pointer"
  onclick="openImageModal('../uploads/photo/<?= $t['profile'] ?>')"
/>

                <?php else: ?>
                  <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">
                    <?= strtoupper(substr($t['name'],0,1)) ?>
                  </div>
                <?php endif; ?>

                <span class="font-semibold"><?= $t['name'] ?></span>
              </div>
            </td>

            <td class="px-6 py-4"><?= $t['phone'] ?></td>
            <td class="px-6 py-4"><?= $t['payment_id'] ?></td>
            <td class="px-6 py-4 text-green-600 font-bold">₹<?= $t['amount'] ?></td>
            <td class="px-6 py-4"><?= $t['date'] ?></td>

            <td class="px-6 py-4">
              <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">Online</span>
            </td>

            <td class="px-6 py-4">
              <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs"><?= $t['status'] ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>

      </table>

    </div>
  </div>

  <!-- MOBILE -->
  <div class="md:hidden space-y-4" id="searchDataMobile">
    <?php foreach($transactions as $t): ?>
    <div class="bg-white p-4 rounded-xl shadow search-row">

      <div class="flex items-start justify-between">
        <div class="flex items-center gap-3">

          <?php if(!empty($t['profile']) && file_exists("../uploads/photo/".$t['profile'])): ?>
            <img 
  src="../uploads/photo/<?= $t['profile'] ?>" 
  class="w-12 h-12 rounded-full border-2 border-orange-400 shadow cursor-pointer"
  onclick="openImageModal('../uploads/photo/<?= $t['profile'] ?>')"
/>

          <?php else: ?>
            <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">
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
document.getElementById("searchInput").addEventListener("keyup", function () {

  var value = this.value.toLowerCase();

  document.querySelectorAll(".search-row").forEach(row => {
    row.style.display =
      row.innerText.toLowerCase().includes(value)
        ? ""
        : "none";
  });

});
</script>

</body>
</html>
