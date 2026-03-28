<?php
include("../connection.php");

// MULTIPLE UPLOAD HANDLER
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
<title>Upload Music</title>

<style>
body {
    font-family: Arial;
    background: #f3f4f6;
    margin: 0;
    padding: 20px;
}

.wrapper {
    display: flex;
    gap: 20px;
    max-width: 1100px;
    margin: auto;
}

.card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    flex: 1;
}

h2 {
    margin-bottom: 15px;
}

input {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
}

button {
    width: 100%;
    padding: 12px;
    background: orange;
    color: white;
    border: none;
    cursor: pointer;
}

#musicList {
    list-style: none;
    padding: 0;
    max-height: 400px;
    overflow-y: auto;
}

#musicList li {
    padding: 8px;
    border-bottom: 1px solid #ddd;
}
</style>

</head>
<body>

<div class="wrapper">

    <!-- LEFT: FORM -->
    <div class="card">
        <h2>Upload Music</h2>

        <form id="uploadForm" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Song Title" required>
            <input type="text" name="artist" placeholder="Artist" required>
            <input type="text" name="tags" placeholder="Tags">

            <!-- MULTIPLE FILE -->
            <input type="file" name="music_file[]" multiple required>

            <button type="submit">Upload</button>
        </form>

        <div id="msg"></div>
    </div>

    <!-- RIGHT: LIST -->
    <div class="card">
        <h2>Uploaded Music</h2>
        <ul id="musicList"></ul>
    </div>

</div>

<script>

// LOAD MUSIC LIST
async function loadMusic() {
    const res = await fetch('get_music.php');
    const data = await res.json();

    const list = document.getElementById('musicList');
    list.innerHTML = '';

    data.forEach(m => {
        list.innerHTML += `<li>${m.title} - ${m.artist}</li>`;
    });
}

// FORM SUBMIT
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

// INITIAL LOAD
loadMusic();

</script>

</body>
</html>
