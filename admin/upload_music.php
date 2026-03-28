<?php
include("../connection.php");

// MULTIPLE UPLOAD + AUTO META FROM FILENAME
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (isset($_FILES['music_file'])) {

        $files = $_FILES['music_file'];
        $total = count($files['name']);
        $uploaded = [];

        for ($i = 0; $i < $total; $i++) {

            $file_name = $files['name'][$i];
            $temp_name = $files['tmp_name'][$i];

            if ($file_name == '') continue;

            // SAFE FILE NAME
            $new_file_name = time() . "_" . rand(1000,9999) . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
            $target_dir = "../uploads/music/";

            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // 🔥 AUTO META FROM FILE NAME
            $fileWithoutExt = pathinfo($file_name, PATHINFO_FILENAME);
            $parts = explode("_", $fileWithoutExt);

            // Artist & Title
            $artist_auto = $parts[0] ?? "Unknown";
            $title_auto  = $parts[1] ?? $fileWithoutExt;

            // Tags
            $tags_array = array_slice($parts, 2);
            $tags_auto = implode(",", $tags_array);

            // Clean text
            $artist_auto = ucwords(strtolower(str_replace("_", " ", $artist_auto)));
            $title_auto = ucwords(strtolower(str_replace("_", " ", $title_auto)));

            if (move_uploaded_file($temp_name, $target_dir . $new_file_name)) {

                $query = "INSERT INTO music (title, artist, file_name, tags) 
                          VALUES ('$title_auto', '$artist_auto', '$new_file_name', '$tags_auto')";
                mysqli_query($con, $query);

                $uploaded[] = $new_file_name;
            }
        }

        echo json_encode([
            "status" => "success",
            "message" => count($uploaded) . " songs uploaded successfully 🚀"
        ]);
        exit;
    }

    echo json_encode(["status" => "error", "message" => "No file uploaded"]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Upload Music</title>

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #0f172a;
    color: #fff;
}

.header {
    display: flex;
    justify-content: space-between;
    padding: 15px 30px;
    background: #020617;
    border-bottom: 1px solid #1e293b;
}

.back-btn {
    background: #22c55e;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;
    color: white;
}

.container {
    display: flex;
    gap: 20px;
    padding: 20px;
}

.card {
    background: #020617;
    padding: 20px;
    border-radius: 12px;
    flex: 1;
}

input {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    background: #0f172a;
    border: none;
    color: white;
}

button {
    width: 100%;
    padding: 12px;
    background: #22c55e;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

#musicList {
    list-style: none;
    padding: 0;
    max-height: 400px;
    overflow-y: auto;
}

#musicList li {
    padding: 10px;
    border-bottom: 1px solid #1e293b;
}
</style>

</head>
<body>

<div class="header">
    <h2>🎵 Music Upload Panel</h2>
    <a href="index.php" class="back-btn">⬅ Dashboard</a>
</div>

<div class="container">

    <!-- Upload -->
    <div class="card">
        <h3>Upload Music</h3>

        <form id="uploadForm" enctype="multipart/form-data">
            <input type="file" name="music_file[]" multiple required>
            <button type="submit">Upload</button>
        </form>

        <div id="msg"></div>
    </div>

    <!-- List -->
    <div class="card">
        <h3>Uploaded Music</h3>
        <ul id="musicList"></ul>
    </div>

</div>

<script>

// LOAD MUSIC
async function loadMusic() {
    const res = await fetch('get_music.php');
    const data = await res.json();

    const list = document.getElementById('musicList');
    list.innerHTML = '';

    data.forEach(m => {
        list.innerHTML += `<li>${m.title} - ${m.artist} (${m.tags})</li>`;
    });
}

// SUBMIT
document.getElementById('uploadForm').onsubmit = async (e) => {
    e.preventDefault();

    const btn = e.target.querySelector('button');
    const msg = document.getElementById('msg');

    btn.disabled = true;
    btn.innerText = "Uploading...";

    const formData = new FormData(e.target);

    const res = await fetch('upload_music.php', {
        method: 'POST',
        body: formData
    });

    const result = await res.json();

    msg.innerText = result.message;

    if (result.status === 'success') {
        e.target.reset();
        loadMusic();
    }

    btn.disabled = false;
    btn.innerText = "Upload";
};

loadMusic();

</script>

</body>
</html>
