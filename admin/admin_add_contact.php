<?php
include("../connection.php");

// Create table if not exists
$sql = "CREATE TABLE IF NOT EXISTS tbl_contact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    mobile VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($con, $sql);

// Get message from session if exists
if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    $msgType = $_SESSION['msgType'];
    unset($_SESSION['msg']);
    unset($_SESSION['msgType']);
} else {
    $msg = "";
    $msgType = "";
}

// Handle Manual Add
if (isset($_POST['add_contact'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $mobile = mysqli_real_escape_string($con, $_POST['mobile']);

    if (!empty($name) && !empty($mobile)) {
        $q = "INSERT INTO tbl_contact (name, mobile) VALUES ('$name', '$mobile')";
        if (mysqli_query($con, $q)) {
            $_SESSION['msg'] = "Contact added successfully!";
            $_SESSION['msgType'] = "success";
        } else {
            $_SESSION['msg'] = "Error: " . mysqli_error($con);
            $_SESSION['msgType'] = "error";
        }
    } else {
        $_SESSION['msg'] = "Please fill all fields.";
        $_SESSION['msgType'] = "error";
    }
    header("Location: admin_add_contact.php");
    exit;
}

// Handle CSV Import
if (isset($_POST['import_csv'])) {
    if ($_FILES['csv_file']['name']) {
        $ext = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'csv') {
            $_SESSION['msg'] = "Invalid file type. Please upload a .CSV file (not .xlsx). In Excel, use 'Save As' -> 'CSV (Comma delimited)'.";
            $_SESSION['msgType'] = "error";
        } else {
            $filename = $_FILES['csv_file']['tmp_name'];
            $file = fopen($filename, "r");
            
            // Skip header
            $header = fgetcsv($file);
            
            $count = 0;
            $errorCount = 0;
            while (($data = fgetcsv($file, 10000, ",")) !== FALSE) {
                // If comma didn't split anything, try semicolon (some Excel versions use it)
                if (count($data) == 1 && strpos($data[0], ';') !== false) {
                    $data = str_getcsv($data[0], ';');
                }

                if (count($data) >= 2) {
                    $name = mysqli_real_escape_string($con, trim($data[0]));
                    $mobile = mysqli_real_escape_string($con, trim($data[1]));
                    
                    if (!empty($name) && !empty($mobile)) {
                        // Basic sanity check to avoid binary garbage
                        if (preg_match('/[^\x20-\x7E\s]/', $name) && strlen($name) > 50) {
                            $errorCount++;
                            continue; 
                        }
                        
                        mysqli_query($con, "INSERT INTO tbl_contact (name, mobile) VALUES ('$name', '$mobile')");
                        $count++;
                    }
                } else {
                    $errorCount++;
                }
            }
            fclose($file);
            
            if ($count > 0) {
                $statusMsg = "Successfully imported $count contacts!";
                if ($errorCount > 0) $statusMsg .= " (Skipped $errorCount invalid rows)";
                $_SESSION['msg'] = $statusMsg;
                $_SESSION['msgType'] = "success";
            } else {
                $_SESSION['msg'] = "No valid contacts found in the file. Please ensure it's a properly formatted CSV.";
                $_SESSION['msgType'] = "error";
            }
        }
    } else {
        $_SESSION['msg'] = "Please select a CSV file.";
        $_SESSION['msgType'] = "error";
    }
    header("Location: admin_add_contact.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($con, "DELETE FROM tbl_contact WHERE id=$id");
    header("Location: admin_add_contact.php");
    exit;
}

// Handle Delete All
if (isset($_GET['delete_all'])) {
    mysqli_query($con, "TRUNCATE TABLE tbl_contact");
    header("Location: admin_add_contact.php");
    exit;
}

include("header.php");
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-gray-800">


    <?php if ($msg): ?>
        <div class="p-4 mb-6 rounded-lg font-medium shadow-sm <?= $msgType == 'success' ? 'bg-green-100 text-green-700 border-l-4 border-green-500' : 'bg-red-100 text-red-700 border-l-4 border-red-500' ?>">
            <?= $msg ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden mb-8">
        <div class="p-4 border-b border-gray-100 flex items-center gap-3 bg-gray-50">
            <a href="index.php" class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
                <i class="fa-solid fa-arrow-left text-orange-600 text-sm"></i>
            </a>
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-address-book text-orange-600"></i>
                Manage Contacts
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Manual Add Form -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
            <div class="bg-gradient-to-r from-orange-400 to-orange-600 px-6 py-4">
                <h2 class="text-white text-lg font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Add Contact Manually
                </h2>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Full Name</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Enter name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Mobile Number</label>
                        <input type="text" name="mobile" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Enter mobile number">
                    </div>
                    <button type="submit" name="add_contact" class="w-full py-3 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg shadow-md transition transform hover:scale-[1.02]">
                        Add Contact
                    </button>
                </form>
            </div>
        </div>

        <!-- CSV Import Form -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                <h2 class="text-white text-lg font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-file-import"></i> Import From CSV (Excel)
                </h2>
            </div>
            <div class="p-6">
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700 mb-4">
                        <p class="font-bold flex items-center gap-2 mb-1">
                            <i class="fa-solid fa-circle-info"></i> Format Guide:
                        </p>
                        <p>Upload a <b>CSV</b> file with two columns:</p>
                        <code class="block mt-1 bg-white p-1 rounded border">Name, Mobile</code>
                        <p class="mt-2 text-xs italic">* First row (header) will be ignored.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Choose CSV File</label>
                        <input type="file" name="csv_file" accept=".csv" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <button type="submit" name="import_csv" class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg shadow-md transition transform hover:scale-[1.02]">
                        Import Contacts
                    </button>
                </form>
            </div>
        </div>
    </div>
  </div>
</div>

    <!-- Contact List Table -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h2 class="font-bold text-gray-800">Saved Contacts</h2>
            <div class="flex items-center gap-4">
                <div class="text-sm font-medium text-gray-500">
                    Total: <?php echo mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as cnt FROM tbl_contact"))['cnt']; ?>
                </div>
                <a href="?delete_all=true" onclick="return confirm('Are you sure you want to delete ALL saved contacts?')" class="text-xs bg-red-100 text-red-600 px-3 py-1 rounded-md hover:bg-red-200 transition font-bold">
                    <i class="fa-solid fa-trash-can mr-1"></i> Delete All
                </a>
            </div>
        </div>
        <div class="overflow-x-auto overflow-y-auto" style="max-height: calc(100vh - 260px);">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-bold">
                    <tr>
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Mobile</th>
                        <th class="px-6 py-4">Created At</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                    $res = mysqli_query($con, "SELECT * FROM tbl_contact ORDER BY id DESC");
                    if (mysqli_num_rows($res) > 0) {
                        while ($row = mysqli_fetch_assoc($res)) {
                            echo "<tr class='hover:bg-orange-50 transition'>";
                            echo "<td class='px-6 py-4 font-medium'>{$row['name']}</td>";
                            echo "<td class='px-6 py-4'>{$row['mobile']}</td>";
                            echo "<td class='px-6 py-4 text-sm text-gray-500'>" . date('d M Y, h:i A', strtotime($row['created_at'])) . "</td>";
                            echo "<td class='px-6 py-4 text-center'>
                                    <a href='?delete_id={$row['id']}' onclick='return confirm(\"Are you sure?\")' class='text-red-500 hover:text-red-700 transition'>
                                        <i class='fa-solid fa-trash'></i>
                                    </a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='px-6 py-10 text-center text-gray-400 italic'>No contacts found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script>
</body>
</html>
