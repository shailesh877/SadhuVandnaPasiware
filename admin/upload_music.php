<?php
include("../connection.php");

// HANDLE REQUEST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // DELETE
    if (isset($_POST['delete_id'])) {

        $id = intval($_POST['delete_id']);

        $res = mysqli_query($con, "SELECT file_name FROM music WHERE id=$id");
        $row = mysqli_fetch_assoc($res);

        if ($row) {

            $filePath = "../uploads/music/" . $row['file_name'];

            mysqli_query($con, "DELETE FROM music WHERE id=$id");

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error"]);
        }

        exit;
    }

    // UPLOAD
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

            // AUTO META
            $fileWithoutExt = pathinfo($file_name, PATHINFO_FILENAME);
            $parts = explode("_", $fileWithoutExt);

            $artist_auto = $parts[0] ?? "Unknown";
            $title_auto  = $parts[1] ?? $fileWithoutExt;

            $tags_array = array_slice($parts, 2);
            $tags_auto = implode(",", $tags_array);

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
            "message" => count($uploaded) . " songs uploaded 🚀"
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Music Panel</title>

<style>
body {
    margin: 0;
    font-family: Arial;
    background: #0f172a;
    color: white;
}

.header {
    display: flex;
    justify-content: space-between;
    padding: 15px 30px;
    background: #020617;
}

.back-btn {
    background: #22c55e;
    padding: 8px 15px;
    border-radius: 6px;
    color: white;
    text-decoration: none;
}

.container {
    display: flex;
    gap: 20px;
    padding: 20px;
}

.card {
    background: #020617;
    padding: 20px;
    border-radius: 10px;
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
    padding: 10px;
    border: none;
    cursor: pointer;
}

.upload-btn {
    width: 100%;
    background: #22c55e;
    border-radius: 8px;
    font-weight: bold;
}

/* 🔥 NEW PREMIUM DELETE BUTTON */
.delete-btn {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: 0.2s;
}

.delete-btn:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    transform: scale(1.05);
}

.delete-btn:active {
    transform: scale(0.95);
}

li {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    align-items: center;
}
</style>

</head>
<body>

<div class="header">
    <h2>🎵 Music Panel</h2>
    <a href="index.php" class="back-btn">⬅ Dashboard</a>
</div>

<div class="container">

    <!-- UPLOAD -->
    <div class="card">
        <h3>Upload Music</h3>

        <form id="uploadForm" enctype="multipart/form-data">
            <input type="file" name="music_file[]" multiple required>
            <button class="upload-btn">Upload</button>
        </form>

        <div id="msg"></div>
    </div>

    <!-- LIST -->
    <div class="card">
        <h3>Music List</h3>
        <ul id="musicList"></ul>
    </div>

</div>

<script>

// LOAD
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
                <small>${m.artist} | ${m.tags}</small>
            </div>
            <button class="delete-btn" onclick="deleteMusic(${m.id})">
                🗑 Delete
            </button>
        </li>
        `;
    });
}

// DELETE
async function deleteMusic(id){

    if(!confirm("⚠️ Are you sure?\nThis song will be permanently deleted!")) return;

    const formData = new FormData();
    formData.append('delete_id', id);

    const res = await fetch('upload_music.php', {
        method: 'POST',
        body: formData
    });

    const result = await res.json();

    if(result.status === 'success'){
        loadMusic();
    } else {
        alert("Delete failed");
    }
}

// UPLOAD
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

    if(result.status === 'success'){
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
