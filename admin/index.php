<?php
include("header.php");
// No need to include connection if header already did, but it's safe.

// Fetch counts dynamically
$newRegCount = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as cnt FROM tbl_members WHERE status='Pending'"))['cnt'];
$approvedCount = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as cnt FROM tbl_members WHERE status='Approved'"))['cnt'];
$blockedCount = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as cnt FROM tbl_members WHERE status='Blocked'"))['cnt'];
$totalEarnings = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(payment_ammount) as total FROM tbl_wallet WHERE status='success'"))['total'];
$totalEarnings = $totalEarnings ? $totalEarnings : 0;

$todayEarnings = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(payment_ammount) as total FROM tbl_wallet WHERE status='success' AND DATE(date) = CURDATE()"))['total'];
$todayEarnings = $todayEarnings ? $todayEarnings : 0;

$chartLabels = [];
$regData = [];
$revData = [];
for($i=6; $i>=0; $i--) {
    $dateStr = date('Y-m-d', strtotime("-$i days"));
    $displayLabel = date('D', strtotime("-$i days"));
    $chartLabels[] = $displayLabel;
    
    $regQ = mysqli_query($con, "SELECT COUNT(*) as c FROM tbl_members WHERE DATE(date) = '$dateStr'");
    $regData[] = mysqli_fetch_assoc($regQ)['c'] ?? 0;
    
    $revQ = mysqli_query($con, "SELECT SUM(payment_ammount) as s FROM tbl_wallet WHERE DATE(date) = '$dateStr' AND status='success'");
    $revData[] = mysqli_fetch_assoc($revQ)['s'] ?? 0;
}
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
 
  <!-- Welcome & Revenue Highlight -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Welcome Banner (Spans 2 cols on lg) -->
      <div class="lg:col-span-2 bg-gradient-to-r from-orange-500 to-red-500 rounded-3xl p-8 sm:p-10 text-white shadow-[0_8px_30px_rgb(0,0,0,0.12)] flex flex-col justify-center relative overflow-hidden">
          <!-- Decorative shapes -->
          <div class="absolute -top-10 -right-10 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
          <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-yellow-500/20 rounded-full blur-xl"></div>
          
          <div class="relative z-10">
              <p class="text-orange-100 mb-2 text-sm font-semibold uppercase tracking-wider"><?= date('l, d F Y') ?></p>
              <h2 class="text-3xl sm:text-4xl font-extrabold mb-3">Welcome Back, Admin! 👋</h2>
              <p class="text-orange-100 max-w-lg text-sm sm:text-base leading-relaxed">Here's your community snapshot. Monitor member growth, manage content, and track revenue seamlessly.</p>
          </div>
      </div>

      <!-- Revenue Highlight Card -->
      <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-3xl p-8 text-white shadow-[0_8px_30px_rgb(0,0,0,0.12)] flex flex-col justify-between relative overflow-hidden">
         <div class="absolute top-0 right-0 p-6 opacity-[0.05]">
             <i class="fa-solid fa-wallet text-8xl"></i>
         </div>
         <div class="relative z-10">
             <div class="flex items-center gap-2 text-gray-400 text-sm font-semibold mb-2">
                 <i class="fa-solid fa-indian-rupee-sign text-emerald-400"></i> Total Revenue
             </div>
             <h3 class="text-4xl sm:text-5xl font-black text-white tracking-tight mb-8">₹<?= number_format($totalEarnings) ?></h3>
             
             <div class="pt-5 border-t border-gray-700/50 flex justify-between items-end">
                 <div>
                     <p class="text-xs text-gray-400 mb-1 uppercase tracking-wider font-semibold">Today's Earnings</p>
                     <p class="text-xl font-bold text-emerald-400">+₹<?= number_format($todayEarnings) ?></p>
                 </div>
                 <a href="admin_wallet.php" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                     <i class="fa-solid fa-arrow-right"></i>
                 </a>
             </div>
         </div>
      </div>
  </div>

  <!-- Overview Stats -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
    
    <!-- New Registration -->
    <a href="admin_new_registration.php" class="bg-white rounded-3xl shadow-sm hover:shadow-xl transition-all duration-300 p-6 border border-gray-100 flex items-center gap-6 group relative overflow-hidden">
      <div class="absolute right-0 top-0 w-32 h-32 bg-orange-50 rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
      <div class="w-16 h-16 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center text-2xl shadow-sm relative z-10 group-hover:scale-110 transition-transform">
        <i class="fa-solid fa-user-plus"></i>
      </div>
      <div class="relative z-10">
          <p class="text-sm font-bold text-gray-500 mb-1 uppercase tracking-wider">Pending</p>
          <h3 class="text-3xl font-black text-gray-800"><?= $newRegCount ?></h3>
      </div>
    </a>

    <!-- All Members -->
    <a href="admin_all_community_member.php" class="bg-white rounded-3xl shadow-sm hover:shadow-xl transition-all duration-300 p-6 border border-gray-100 flex items-center gap-6 group relative overflow-hidden">
      <div class="absolute right-0 top-0 w-32 h-32 bg-green-50 rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
      <div class="w-16 h-16 rounded-2xl bg-green-100 text-green-600 flex items-center justify-center text-2xl shadow-sm relative z-10 group-hover:scale-110 transition-transform">
        <i class="fa-solid fa-users"></i>
      </div>
      <div class="relative z-10">
          <p class="text-sm font-bold text-gray-500 mb-1 uppercase tracking-wider">Active Members</p>
          <h3 class="text-3xl font-black text-gray-800"><?= $approvedCount ?></h3>
      </div>
    </a>

    <!-- Blocked Members -->
    <a href="admin_block_user.php" class="bg-white rounded-3xl shadow-sm hover:shadow-xl transition-all duration-300 p-6 border border-gray-100 flex items-center gap-6 group relative overflow-hidden">
      <div class="absolute right-0 top-0 w-32 h-32 bg-red-50 rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
      <div class="w-16 h-16 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center text-2xl shadow-sm relative z-10 group-hover:scale-110 transition-transform">
        <i class="fa-solid fa-user-slash"></i>
      </div>
      <div class="relative z-10">
          <p class="text-sm font-bold text-gray-500 mb-1 uppercase tracking-wider">Blocked</p>
          <h3 class="text-3xl font-black text-gray-800"><?= $blockedCount ?></h3>
      </div>
    </a>
  </div>

  <!-- Charts Section -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Registrations Chart -->
      <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 sm:p-8">
          <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-bold text-gray-800">User Growth</h3>
              <span class="text-xs font-semibold bg-gray-100 text-gray-600 px-3 py-1 rounded-full">Last 7 Days</span>
          </div>
          <div class="relative h-72 w-full">
              <canvas id="regChart"></canvas>
          </div>
      </div>

      <!-- Revenue Chart -->
      <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 sm:p-8">
          <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-bold text-gray-800">Revenue Analytics</h3>
              <span class="text-xs font-semibold bg-emerald-50 text-emerald-600 border border-emerald-100 px-3 py-1 rounded-full">Last 7 Days</span>
          </div>
          <div class="relative h-72 w-full">
              <canvas id="revChart"></canvas>
          </div>
      </div>
      
  </div>

</main>

<script>
// Parse data from PHP
const labels = <?= json_encode($chartLabels) ?>;
const regData = <?= json_encode($regData) ?>;
const revData = <?= json_encode($revData) ?>;

// Registration Line Chart
const ctxReg = document.getElementById('regChart').getContext('2d');
new Chart(ctxReg, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'New Users',
            data: regData,
            borderColor: '#f97316', // orange-500
            backgroundColor: 'rgba(249, 115, 22, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#f97316',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { borderDash: [4, 4], color: '#f3f4f6' },
                ticks: { padding: 10, font: { family: 'inherit' } }
            },
            x: {
                grid: { display: false },
                ticks: { padding: 10, font: { family: 'inherit' } }
            }
        },
        interaction: { mode: 'index', intersect: false }
    }
});

// Revenue Bar Chart
const ctxRev = document.getElementById('revChart').getContext('2d');
new Chart(ctxRev, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Revenue (₹)',
            data: revData,
            backgroundColor: '#10b981', // emerald-500
            borderRadius: 6,
            barThickness: 'flex',
            maxBarThickness: 32
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { borderDash: [4, 4], color: '#f3f4f6' },
                ticks: {
                    padding: 10,
                    font: { family: 'inherit' },
                    callback: function(value) {
                        if (value >= 1000) return '₹' + (value/1000) + 'k';
                        return '₹' + value;
                    }
                }
            },
            x: {
                grid: { display: false },
                ticks: { padding: 10, font: { family: 'inherit' } }
            }
        },
        interaction: { mode: 'index', intersect: false }
    }
});
</script>

</body>
</html>
