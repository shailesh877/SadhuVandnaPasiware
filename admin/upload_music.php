<?php
include("../connection.php");

// MULTIPLE UPLOAD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $title = mysqli_real_escape_string($con, $_POST['title']);
    $artist = mysqli_real_escape_string($con, $_POST['artist']);
    $tags = mysqli_real_escape_string($con, $_POST['tags']);

    if (isset($_FILES['music_file'])) {

        $files = $_FILES['music_file'];
        $total = count($files['name']);
        $uploaded = [];

        for ($i = 0; $i < $total; $i++) {

            $file_name = $files['name'][$i];
            $temp_name = $files['tmp_name'][$i];

            if ($file_name == '') continue;

            $new_file_name = time() . "_" . rand(1000,9999) . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
            $target_dir = "../uploads/music/";

            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            if (move_uploaded_file($temp_name, $target_dir . $new_file_name)) {

                $query = "INSERT INTO music (title, artist, file_name, tags) 
                          VALUES ('$title', '$artist', '$new_file_name', '$tags')";
                mysqli_query($con, $query);

                $uploaded[] = $new_file_name;
            }
        }

        echo json_encode([
            "status" => "success",
            "message" => count($uploaded) . " files uploaded successfully"
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

/* HEADER */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    background: #020617;
    border-bottom: 1px solid #1e293b;
}

.header h1 {
    font-size: 20px;
}

.back-btn {
    background: #22c55e;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;
    color: white;
    font-weight: bold;
}

/* LAYOUT */
.container {
    display: flex;
    gap: 20px;
    padding: 20px;
}

/* CARD */
.card {
    background: #020617;
    border-radius: 12px;
    padding: 20px;
    flex: 1;
    box-shadow: 0 0 20px rgba(0,0,0,0.4);
}

/* INPUT */
input {
    width: 100%;
    padding: 10px;
    margin-bottom: 12px;
    border-radius: 6px;
    border: none;
    background: #0f172a;
    color: white;
}

input:focus {
    outline: 2px solid #22c55e;
}

/* BUTTON */
button {
    width: 100%;
    padding: 12px;
    background: #22c55e;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
}

/* LIST */
#musicList {
    list-style: none;
    padding: 0;
    max-height: 400px;
    overflow-y: auto;
}

#musicList li {
    padding: 10px;
    border-bottom: 1px solid #1e293b;
    display: flex;
    justify-content: space-between;
}

.tag {
    font-size: 12px;
    color: #94a3b8;
}

#msg {
    margin-top: 10px;
    font-size: 14px;
}
</style>

</head>
<body>

<!-- HEADER -->
<div class="header">
    <h1>🎵 Music Upload Panel</h1>
    <a href="index.php" class="back-btn">⬅ Back to Dashboard</a>
</div>

<!-- MAIN -->
<div class="container">

    <!-- UPLOAD -->
    <div class="card">
        <h2>Upload Music</h2>

        <form id="uploadForm" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Song Title" required>
            <input type="text" name="artist" placeholder="Artist Name" required>
            <input type="text" name="tags" placeholder="Tags (comma separated)">
            <input type="file" name="music_file[]" multiple required>

            <button type="submit">Upload Music</button>
        </form>

        <div id="msg"></div>
    </div>

    <!-- LIST -->
    <div class="card">
        <h2>Uploaded Music</h2>
        <ul id="musicList"></ul>
    </div>

</div>

<script>

// LOAD LIST
async function loadMusic() {
    const res = await fetch('get_music.php');
    const data = await res.json();

    const list = document.getElementById('musicList');
    list.innerHTML = '';

    data.forEach(m => {
        list.innerHTML += `
            <li>
                <div>
                    <b>${m.title}</b><br>
                    <span class="tag">${m.artist}</span>
                </div>
            </li>
        `;
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
    btn.innerText = "Upload Music";
};

// INIT
loadMusic();

</script>

</body>
</html>
