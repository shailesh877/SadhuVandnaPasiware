<?php
session_start();
include("../connection.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

date_default_timezone_set("Asia/Kolkata");

/* ============= ADD / UPLOAD ===============*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['music'])) {
    
    $files = $_FILES['music'];
    $total = count($files['name']);
    $uploaded = 0;

    $createTable = "CREATE TABLE IF NOT EXISTS tbl_music (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        artist VARCHAR(255),
        file_name VARCHAR(255) NOT NULL,
        tags TEXT,
        file_hash VARCHAR(64),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($con, $createTable);

    $uploadDir = __DIR__ . '/../uploads/music/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    for ($i = 0; $i < $total; $i++) {
        $file_name = $files['name'][$i];
        $temp_name = $files['tmp_name'][$i];
        $error = $files['error'][$i];

        if ($file_name == '' || $error !== 0) continue;

        $new_file_name = time() . "_" . rand(1000,9999) . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
        
        $hash = sha1_file($temp_name);
        
        // check duplicate by hash
        $chk = mysqli_prepare($con, "SELECT id FROM tbl_music WHERE file_hash = ? LIMIT 1");
        mysqli_stmt_bind_param($chk, 's', $hash);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        $is_dup = mysqli_stmt_num_rows($chk) > 0;
        mysqli_stmt_close($chk);
        
        if ($is_dup) continue;

        // AUTO META
        $fileWithoutExt = pathinfo($file_name, PATHINFO_FILENAME);
        $parts = explode("_", $fileWithoutExt);

        $artist_auto = $parts[0] ?? "Unknown";
        $title_auto  = $parts[1] ?? $fileWithoutExt;

        $tags_array = array_slice($parts, 2);
        $tags_auto = implode(",", $tags_array);

        $artist_auto = ucwords(strtolower(str_replace("_", " ", $artist_auto)));
        $title_auto = ucwords(strtolower(str_replace("_", " ", $title_auto)));

        if (move_uploaded_file($temp_name, $uploadDir . $new_file_name)) {
            $stmt = mysqli_prepare($con, "INSERT INTO tbl_music (title, artist, file_name, tags, file_hash) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sssss', $title_auto, $artist_auto, $new_file_name, $tags_auto, $hash);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $uploaded++;
        }
    }
    
    header("Location: music.php?msg=".urlencode("$uploaded songs uploaded successfully!")."&type=success");
    exit;
}

/* ============= DELETE ===============*/
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $old = mysqli_fetch_assoc(mysqli_query($con, "SELECT file_name FROM tbl_music WHERE id='$id'"));
    if ($old && $old['file_name']) {
        $file = __DIR__ . '/../uploads/music/' . $old['file_name'];
        if (file_exists($file)) unlink($file);
    }
    mysqli_query($con, "DELETE FROM tbl_music WHERE id='$id'");
    header("Location: music.php?msg=".urlencode("Music Deleted")."&type=success");
    exit;
}

$data = mysqli_query($con, "SELECT * FROM tbl_music ORDER BY id DESC");
?>

<?php include("header.php"); ?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

  <!-- Secondary Header -->
  <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50">
      <div class="flex items-center gap-3">
        <a href="index.php" class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center hover:bg-orange-200 transition shadow-sm">
          <i class="fa-solid fa-arrow-left text-orange-600 text-sm"></i>
        </a>
        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
          <i class="fa-solid fa-music text-orange-600"></i>
          Music Management
        </h2>
      </div>
    </div>
  </div>

  <div class="flex flex-col md:flex-row gap-8">

  <!-- ================= ADD FORM ================= -->
  <div class="w-full md:w-1/3">
    <div class="bg-white rounded-xl shadow-lg p-6">
      <?php
      if(isset($_GET['msg'])){
        $color = (isset($_GET['type']) && $_GET['type']=='success') ? 'green' : 'red';
        echo "<p class='mb-3 text-".$color."-600 font-semibold text-sm'>" . htmlspecialchars($_GET['msg']) . "</p>";
      }
      ?>

      <div class="flex items-center gap-2 mb-4">
        <i class="fa-solid fa-music text-orange-600"></i>
        <h2 class="text-lg font-bold">Add Music</h2>
      </div>

      <form method="POST" enctype="multipart/form-data" class="space-y-4">

        <div>
          <label class="block text-sm font-medium mb-1">Upload Audio Files</label>
          <input type="file" name="music[]" accept="audio/*" multiple required
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm">
          <p class="text-xs text-gray-500 mt-1">Allowed: mp3, wav, m4a, ogg. Max 20MB per file. Select multiple files to upload at once.</p>
        </div>

        <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-2 rounded-lg font-semibold text-sm">Upload Tracks</button>

      </form>
    </div>
  </div>

  <!-- ================= LIST VIEW ================= -->
  <div class="w-full md:w-2/3">
    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6">

      <div class="flex items-center gap-2 mb-4">
        <i class="fa-solid fa-list-music text-orange-600"></i>
        <h2 class="text-lg font-bold">Uploaded Tracks</h2>
      </div>

      <div class="overflow-x-auto max-h-[500px] relative border border-orange-200 rounded-lg">
        <table class="min-w-full text-sm">
          <thead class="bg-orange-500 text-white sticky top-0 z-10">
            <tr>
              <th class="px-4 py-2 text-left whitespace-nowrap">Sr No</th>
              <th class="px-4 py-2 text-left whitespace-nowrap">Title</th>
              <th class="px-4 py-2 text-left whitespace-nowrap">Artist</th>
              <th class="px-4 py-2 text-left whitespace-nowrap">Tags</th>
              <th class="px-4 py-2 text-left whitespace-nowrap">Preview</th>
              <th class="px-4 py-2 text-left whitespace-nowrap">Date</th>
              <th class="px-4 py-2 text-center whitespace-nowrap">Action</th>
            </tr>
          </thead>

          <tbody class="divide-y bg-white">
          <?php $sr = 1; while($row = mysqli_fetch_assoc($data)) { ?>
            <tr class="hover:bg-orange-50">
              <td class="px-4 py-2 font-semibold"><?= $sr++ ?></td>
              <td class="px-4 py-2 font-medium"><?= htmlspecialchars($row['title']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($row['artist']) ?></td>
              <td class="px-4 py-2 text-xs text-gray-600"><?= htmlspecialchars($row['tags']) ?></td>
              <td class="px-4 py-2">
                <audio controls class="max-w-xs">
                  <source src="../uploads/music/<?= htmlspecialchars($row['file_name']) ?>" />
                </audio>
              </td>
              <td class="px-4 py-2 whitespace-nowrap text-gray-600"><?= date("d M Y", strtotime($row['created_at'])) ?></td>
              <td class="px-4 py-2 text-center flex gap-2 justify-center">
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this track?')" class="bg-red-100 text-red-600 w-8 h-8 flex items-center justify-center rounded">
                  <i class="fa-solid fa-trash"></i>
                </a>
              </td>
            </tr>
          <?php } ?>

          <?php if(mysqli_num_rows($data) == 0){ ?>
            <tr>
              <td colspan="7" class="text-center py-4 text-gray-500">No tracks uploaded yet.</td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>

  </div> <!-- End Flex Container -->

</main>

</body>
</html>


