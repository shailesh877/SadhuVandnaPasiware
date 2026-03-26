<?php
include("../connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $artist = mysqli_real_escape_string($con, $_POST['artist']);
    $tags = mysqli_real_escape_string($con, $_POST['tags']); // Comma-separated

    if (isset($_FILES['music_file'])) {
        $file_name = $_FILES['music_file']['name'];
        $temp_name = $_FILES['music_file']['tmp_name'];
        
        $new_file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
        $target_dir = "../uploads/music/";
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (move_uploaded_file($temp_name, $target_dir . $new_file_name)) {
            $query = "INSERT INTO music (title, artist, file_name, tags) 
                      VALUES ('$title', '$artist', '$new_file_name', '$tags')";
            
            if (mysqli_query($con, $query)) {
                echo json_encode(array("status" => "success", "message" => "Music uploaded successfully"));
            } else {
                echo json_encode(array("status" => "error", "message" => mysqli_error($con)));
            }
        } else {
            echo json_encode(array("status" => "error", "message" => "Failed to move uploaded file"));
        }
    } else {
        echo json_encode(array("status" => "error", "message" => "No file uploaded"));
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Upload Music</title>
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #f3f4f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 2.5rem; borderRadius: 1.5rem; boxShadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 450px; }
        h1 { margin-bottom: 1.5rem; color: #111827; font-size: 1.75rem; font-weight: 800; textAlign: center; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; color: #4b5563; font-weight: 500; font-size: 0.875rem; }
        input[type="text"], input[type="file"] { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; borderRadius: 0.75rem; fontSize: 1rem; box-sizing: border-box; transition: border-color 0.2s; }
        input[type="text"]:focus { outline: none; border-color: #ea580c; ring: 2px solid #ffedd5; }
        .help-text { font-size: 0.75rem; color: #6b7280; marginTop: 0.25rem; }
        button { width: 100%; padding: 1rem; background-color: #ea580c; color: white; border: none; borderRadius: 0.75rem; fontSize: 1.125rem; fontWeight: 700; cursor: pointer; transition: background-color 0.2s, transform 0.1s; marginTop: 1rem; }
        button:hover { background-color: #d9480f; }
        button:active { transform: scale(0.98); }
        #message { margin-top: 1.5rem; padding: 1rem; borderRadius: 0.75rem; display: none; textAlign: center; font-weight: 500; }
        .success { background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🎵 Upload Music</h1>
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Song Title</label>
                <input type="text" id="title" name="title" placeholder="e.g. Husn" required>
            </div>
            <div class="form-group">
                <label for="artist">Artist Name</label>
                <input type="text" id="artist" name="artist" placeholder="e.g. Anuv Jain" required>
            </div>
            <div class="form-group">
                <label for="tags">Keywords / Tags</label>
                <input type="text" id="tags" name="tags" placeholder="e.g. sad, romantic, slow">
                <p class="help-text">Separate tags with a comma (,)</p>
            </div>
            <div class="form-group">
                <label for="music_file">MP3 File</label>
                <input type="file" id="music_file" name="music_file" accept="audio/mpeg" required>
            </div>
            <button type="submit">Upload to Post Library</button>
        </form>
        <div id="message"></div>
    </div>

    <script>
        document.getElementById('uploadForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const msg = document.getElementById('message');
            
            btn.disabled = true;
            btn.innerText = 'Uploading...';
            msg.style.display = 'none';

            const formData = new FormData(e.target);
            try {
                const response = await fetch('upload_music.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                msg.style.display = 'block';
                msg.className = result.status === 'success' ? 'success' : 'error';
                msg.innerText = result.message;
                
                if(result.status === 'success') e.target.reset();
            } catch (err) {
                msg.style.display = 'block';
                msg.className = 'error';
                msg.innerText = 'Upload failed. Check your network.';
            } finally {
                btn.disabled = false;
                btn.innerText = 'Upload to Post Library';
            }
        };
    </script>
</body>
</html>
