<?php
session_start();
include("../connection.php");

// ============================================
// MSG91 WhatsApp Configuration
// ============================================
define('MSG91_AUTH_KEY', '495236Ar0Le3hg86996e6d6P1');
define('MSG91_INTEGRATED_NUMBER', '919328754474');

$msg = "";
$msgType = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $template_name = 'new_update';
    $language_code = 'en';
    $feature_name = trim($_POST['feature_name'] ?? 'New Update');

    // Handle selection from JSON (Now the single source of truth)
    $ids = [];
    if(!empty($_POST['selected_users_json'])){
        $ids = json_decode($_POST['selected_users_json'], true);
    }

    $query = "";
    if (!empty($ids)) {
        $ids_str = implode(',', array_map('intval', $ids));
        $query = "SELECT name, mobile FROM tbl_members WHERE id IN ($ids_str) AND mobile IS NOT NULL AND mobile != ''";
    }

    if (empty($query)) {
        $msg = "No users selected! Please make sure at least one member is checked.";
        $msgType = "error";
    } else {
        $res = mysqli_query($con, $query);
        $all_numbers = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $num = preg_replace('/[^0-9]/', '', $row['mobile']);
            
            // Remove leading zero if present (e.g., 09876543210 -> 9876543210)
            if (strlen($num) == 11 && substr($num, 0, 1) === '0') {
                $num = substr($num, 1);
            }

            // If it's a 10-digit number, prepend 91
            if (strlen($num) == 10) {
                $num = "91" . $num;
            } 
            // If it's longer than 10 but doesn't start with 91, prepend 91
            elseif (strlen($num) > 10 && substr($num, 0, 2) !== '91') {
                $num = "91" . $num;
            }

            if (!empty($num)) {
                $all_numbers[] = $num;
            }
        }

        if (empty($all_numbers)) {
            $msg = "No valid mobile numbers found for selection.";
            $msgType = "error";
        } else {
            // Group all numbers into one components block as they share the same variable
            $to_and_components = [
                [
                    "to" => $all_numbers,
                    "components" => [
                        "body_1" => [
                            "type" => "text",
                            "value" => $feature_name
                        ]
                    ]
                ]
            ];
            // Send to MSG91 API
            $payload_data = [
                'integrated_number' => MSG91_INTEGRATED_NUMBER,
                'content_type' => 'template',
                'payload' => [
                    'messaging_product' => 'whatsapp',
                    'type' => 'template',
                    'template' => [
                        'name' => $template_name,
                        'language' => [
                            'code' => $language_code,
                            'policy' => 'deterministic'
                        ],
                        'namespace' => '18789e67_fb8c_4cf7_83a4_ae0b3b9e75d1',
                        'to_and_components' => $to_and_components
                    ]
                ]
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/bulk/",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($payload_data),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "authkey: " . trim(MSG91_AUTH_KEY)
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $json_payload = json_encode($payload_data, JSON_PRETTY_PRINT);
            curl_close($curl);

            if ($err) {
                $_SESSION['msg'] = "cURL Error #:" . $err;
                $_SESSION['msgType'] = "error";
            } else {
                $res_json = json_decode($response, true);
                if (isset($res_json['status']) && $res_json['status'] == 'error' || (isset($res_json['hasError']) && $res_json['hasError'] == true)) {
                     $msg_text = $res_json['message'] ?? 'Check MSG91 dashboard logs';
                     $_SESSION['msg'] = "MSG91 Error: " . $msg_text . "<br><br><b>Sent JSON:</b><pre style='background:#f4f4f4;padding:10px;font-size:10px;'>".htmlspecialchars($json_payload)."</pre>";
                     $_SESSION['msgType'] = "error";
                } else {
                    $_SESSION['msg'] = "Campaign sent successfully! " . count($all_numbers) . " messages initiated.";
                    $_SESSION['msgType'] = "success";
                }
            }
            header("Location: admin_whatsapp_bulk.php");
            exit;
        }
    }
}

// Get message from session if exists
if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    $msgType = $_SESSION['msgType'];
    unset($_SESSION['msg']);
    unset($_SESSION['msgType']);
}
?>

<?php include("header.php"); ?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  <div class="flex items-center gap-3 mb-6">
    <a href="index.php" class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition">
      <i class="fa-solid fa-arrow-left text-orange-600"></i>
    </a>
    <div>
      <h1 class="text-2xl font-bold text-gray-800">Bulk WhatsApp Messaging</h1>
      <p class="text-sm text-gray-500">Send "New Update" notification via MSG91</p>
    </div>
  </div>

  <?php if (!empty($msg)): ?>
    <div class="p-4 mb-6 rounded-lg font-medium shadow-sm <?php echo $msgType == 'success' ? 'bg-green-100 text-green-700 border-l-4 border-green-500' : 'bg-red-100 text-red-700 border-l-4 border-red-500'; ?>">
        <?php echo htmlspecialchars($msg); ?>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8 border border-gray-100">
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
        <h2 class="text-white text-lg font-semibold flex items-center gap-2">
            <i class="fa-brands fa-whatsapp text-2xl"></i> Send Community Update
        </h2>
    </div>
    
    <div class="p-6">
        <form method="POST" id="whatsappForm" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- LEFT COLUMN: Configurations -->
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200 shadow-sm">
                    <label class="block text-sm font-bold text-gray-800 mb-3">1. Target Audience</label>
                    <div class="flex flex-col gap-3 mb-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="radio" name="target_type" value="all" checked class="w-5 h-5 text-green-600 focus:ring-green-500 cursor-pointer" onchange="toggleSections()">
                            <span class="text-sm text-gray-700 font-medium">All Members</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="radio" name="target_type" value="status" class="w-5 h-5 text-green-600 focus:ring-green-500 cursor-pointer" onchange="toggleSections()">
                            <span class="text-sm text-gray-700 font-medium">Filter by Status</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="radio" name="target_type" value="specific" class="w-5 h-5 text-green-600 focus:ring-green-500 cursor-pointer" onchange="toggleSections()">
                            <span class="text-sm text-gray-700 font-medium">Specific Members</span>
                        </label>
                    </div>

                    <div id="statusSection" class="hidden mt-3 pt-3 border-t border-gray-200">
                        <label class="block text-sm text-gray-600 mb-2">Select User Status</label>
                        <select name="status_filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none">
                            <option value="Approved">Approved (Active)</option>
                            <option value="Pending">Pending (Inactive)</option>
                        </select>
                    </div>
                </div>

                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200 shadow-sm">
                    <label class="block text-sm font-bold text-gray-800 mb-3">2. Template Preview</label>
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-800 italic leading-relaxed">
                        Hello Sadhuvandna Community, We have introduced a new feature: <span class="font-bold text-blue-900 bg-blue-200 px-1 rounded">{{Feature Name}}</span>. Please check the app to explore the update. Thank you.
                    </div>
                </div>

                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200 shadow-sm">
                    <label class="block text-sm font-bold text-gray-800 mb-3">3. New Feature Name</label>
                    <input type="text" name="feature_name" placeholder="E.g. Attendance Report" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none shadow-sm">
                </div>

                <button type="submit" class="w-full py-4 px-4 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all flex justify-center items-center gap-2 text-xl">
                    <i class="fa-solid fa-paper-plane"></i> Send Now
                </button>
            </div>
            
            <!-- RIGHT COLUMN: User List -->
            <div class="lg:col-span-8 flex flex-col h-full bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-3">
                    <h3 class="text-xl font-bold text-gray-800"><i class="fa-solid fa-list-check text-green-500 mr-2"></i> Member Selection</h3>
                    <div class="flex gap-2">
                        <button type="button" onclick="selectAllFiltered()" class="px-3 py-1 bg-green-100 hover:bg-green-200 text-green-700 text-sm font-semibold rounded-lg transition border border-green-200"><i class="fa-solid fa-check-double mr-1"></i> Select All</button>
                        <button type="button" onclick="clearSelection()" class="px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-sm font-semibold rounded-lg transition border border-red-200"><i class="fa-solid fa-xmark mr-1"></i> Clear</button>
                    </div>
                </div>

                <div id="specificSection" class="flex flex-col flex-1 h-full">
                    <div class="flex justify-between items-center mb-4">
                        <input type="text" id="memberSearch" placeholder="Search by name or mobile..." class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 w-full sm:w-72 shadow-sm">
                        <span class="text-sm font-bold text-green-800 bg-green-100 px-3 py-1 rounded-full border border-green-200">Selected: <span id="selectedCount">0</span></span>
                    </div>
                    
                    <div class="border border-gray-300 rounded-xl bg-white overflow-hidden shadow-sm flex flex-col flex-1 min-h-[400px]">
                        <div class="bg-gray-50 px-5 py-3 border-b border-gray-300">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" id="selectAllPage" class="w-5 h-5 text-green-600 rounded cursor-pointer" onchange="toggleSelectAllPage()">
                                <span class="text-sm font-bold text-gray-700">Select All (Page)</span>
                            </label>
                        </div>
                        
                        <div class="flex-1 overflow-y-auto max-h-[450px]" id="userListContainer"></div>
                        
                        <div class="bg-gray-50 px-5 py-3 border-t border-gray-300 flex justify-between items-center">
                            <button type="button" onclick="prevPage()" class="px-5 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-100 disabled:opacity-50 font-bold">Previous</button>
                            <span class="text-sm text-gray-600 font-bold">Page <span id="currentPageLabel">1</span> of <span id="totalPagesLabel">1</span></span>
                            <button type="button" onclick="nextPage()" class="px-5 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-100 disabled:opacity-50 font-bold">Next</button>
                        </div>
                    </div>
                    <input type="hidden" name="selected_users_json" id="selectedUsersJson" value="[]">
                </div>
            </div>
        </form>
    </div>
  </div>
</main>

<?php
$js_users = [];
$all_users = mysqli_query($con, "SELECT id, name, mobile, status FROM tbl_members ORDER BY name ASC");
while($u = mysqli_fetch_assoc($all_users)){
    if(empty($u['mobile'])) continue;
    $js_users[] = [
        'id' => $u['id'],
        'name' => htmlspecialchars($u['name'] ?? ''),
        'mobile' => htmlspecialchars($u['mobile'] ?? ''),
        'status' => $u['status']
    ];
}
?>

<script>
const users = <?= json_encode($js_users) ?>;
let filteredUsers = [...users];
let currentPage = 1;
const itemsPerPage = 50;
let selectedIds = new Set();

function toggleSections() {
    const targetType = document.querySelector('input[name="target_type"]:checked').value;
    const statusSection = document.getElementById('statusSection');
    
    // Clear previous selection to avoid mixing modes
    selectedIds.clear();

    if (targetType === 'all') {
        statusSection.classList.add('hidden');
        filteredUsers = [...users];
        // Auto-select all for "All Members"
        filteredUsers.forEach(u => selectedIds.add(String(u.id)));
    } else if (targetType === 'status') {
        statusSection.classList.remove('hidden');
        filterByStatus();
        return;
    } else if (targetType === 'specific') {
        statusSection.classList.add('hidden');
        filteredUsers = [...users];
        // Leave selection empty for manual choice
    }
    
    document.getElementById('selectedCount').innerText = selectedIds.size;
    currentPage = 1;
    renderList();
}

function filterByStatus() {
    const statusVal = document.querySelector('select[name="status_filter"]').value;
    selectedIds.clear(); // Reset selection for the new filter
    
    filteredUsers = users.filter(u => u.status === statusVal);
    // Auto-select all users matching this status
    filteredUsers.forEach(u => selectedIds.add(String(u.id)));
    
    document.getElementById('selectedCount').innerText = selectedIds.size;
    currentPage = 1;
    renderList();
}

document.querySelector('select[name="status_filter"]').addEventListener('change', filterByStatus);

function renderList() {
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const pageUsers = filteredUsers.slice(start, end);
    const container = document.getElementById('userListContainer');
    container.innerHTML = '';
    
    if(pageUsers.length === 0) {
        container.innerHTML = '<div class="p-4 text-center text-sm text-gray-500 font-medium">No members found.</div>';
    }
    
    pageUsers.forEach(u => {
        const isChecked = selectedIds.has(String(u.id));
        const label = document.createElement('label');
        label.className = 'flex items-center gap-3 p-3 hover:bg-green-50 cursor-pointer border-b border-gray-50 last:border-0 transition';
        label.innerHTML = `
            <input type="checkbox" value="${u.id}" ${isChecked ? 'checked' : ''} class="w-4 h-4 text-green-600 rounded cursor-pointer" onchange="toggleUserSelection('${u.id}', this.checked)">
            <div>
                <div class="text-sm text-gray-800 font-bold">${u.name}</div>
                <div class="text-[10px] text-gray-500">${u.mobile} • ${u.status}</div>
            </div>
        `;
        container.appendChild(label);
    });
    
    document.getElementById('currentPageLabel').innerText = currentPage;
    document.getElementById('totalPagesLabel').innerText = Math.ceil(filteredUsers.length / itemsPerPage) || 1;
    updateHiddenInputs();
}

function toggleUserSelection(id, isChecked) {
    if(isChecked) selectedIds.add(String(id));
    else selectedIds.delete(String(id));
    document.getElementById('selectedCount').innerText = selectedIds.size;
    updateHiddenInputs();
}

function toggleSelectAllPage() {
    const isChecked = document.getElementById('selectAllPage').checked;
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    filteredUsers.slice(start, end).forEach(u => {
        if(isChecked) selectedIds.add(String(u.id));
        else selectedIds.delete(String(u.id));
    });
    document.getElementById('selectedCount').innerText = selectedIds.size;
    renderList();
}

function prevPage() { if(currentPage > 1) { currentPage--; renderList(); } }
function nextPage() { if(currentPage < Math.ceil(filteredUsers.length / itemsPerPage)) { currentPage++; renderList(); } }

function selectAllFiltered() {
    filteredUsers.forEach(u => selectedIds.add(String(u.id)));
    document.getElementById('selectedCount').innerText = selectedIds.size;
    renderList();
}

function clearSelection() {
    selectedIds.clear();
    document.getElementById('selectedCount').innerText = 0;
    renderList();
}

function updateHiddenInputs() {
    document.getElementById('selectedUsersJson').value = JSON.stringify(Array.from(selectedIds));
}

document.getElementById('memberSearch').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    filteredUsers = users.filter(u => u.name.toLowerCase().includes(term) || u.mobile.includes(term));
    currentPage = 1;
    renderList();
});

renderList();
</script>
</body>
</html>
