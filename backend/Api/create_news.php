<?php
include 'headers.php';
include 'connection.php';

// Check if POST body is empty due to post_max_size limit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    echo json_encode(["status" => "error", "message" => "Uploaded media file is too large for server limits. Please select a smaller file."]);
    exit;
}

// Read input from POST, REQUEST, or JSON body
$jsonInput = json_decode(file_get_contents('php://input'), true);

$title = trim($_POST['title'] ?? $_REQUEST['title'] ?? $jsonInput['title'] ?? '');
$description = trim($_POST['description'] ?? $_REQUEST['description'] ?? $jsonInput['description'] ?? '');
$category = trim($_POST['category'] ?? $_REQUEST['category'] ?? $jsonInput['category'] ?? 'ताज़ा खबर');
$state = trim($_POST['state'] ?? $_REQUEST['state'] ?? $jsonInput['state'] ?? '');
$district = trim($_POST['district'] ?? $_REQUEST['district'] ?? $jsonInput['district'] ?? '');
$user_id = intval($_POST['user_id'] ?? $_REQUEST['user_id'] ?? $jsonInput['user_id'] ?? 0);

if (empty($title) || empty($description)) {
    echo json_encode(["status" => "error", "message" => "Title and description are required."]);
    exit;
}

$uploadDir = "../uploads/news/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

$mediaFiles = [];

// Handle multiple uploaded files under 'media', 'media[]', or 'image'
$filesToProcess = null;
if (isset($_FILES['media'])) {
    $filesToProcess = $_FILES['media'];
} elseif (isset($_FILES['media[]'])) {
    $filesToProcess = $_FILES['media[]'];
}

if ($filesToProcess && !empty($filesToProcess['name'])) {
    if (is_array($filesToProcess['name'])) {
        foreach ($filesToProcess['name'] as $key => $filename) {
            if (empty($filename)) continue;
            $tmpName = $filesToProcess['tmp_name'][$key];
            $newName = time() . "_" . rand(1000, 9999) . "_" . preg_replace('/[^a-zA-Z0-9_\.-]/', '', basename($filename));
            $targetFile = $uploadDir . $newName;

            if (move_uploaded_file($tmpName, $targetFile)) {
                $mediaFiles[] = $newName;
            }
        }
    } else {
        $filename = $filesToProcess['name'];
        $tmpName = $filesToProcess['tmp_name'];
        $newName = time() . "_" . rand(1000, 9999) . "_" . preg_replace('/[^a-zA-Z0-9_\.-]/', '', basename($filename));
        $targetFile = $uploadDir . $newName;
        if (move_uploaded_file($tmpName, $targetFile)) {
            $mediaFiles[] = $newName;
        }
    }
}

if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
    $filename = $_FILES['image']['name'];
    $tmpName = $_FILES['image']['tmp_name'];
    $newName = time() . "_" . rand(1000, 9999) . "_" . preg_replace('/[^a-zA-Z0-9_\.-]/', '', basename($filename));
    $targetFile = $uploadDir . $newName;
    if (move_uploaded_file($tmpName, $targetFile)) {
        $mediaFiles[] = $newName;
    }
}

$mediaString = !empty($mediaFiles) ? implode(",", $mediaFiles) : '';
date_default_timezone_set("Asia/Kolkata");
$date = date("Y-m-d H:i:s");

$stmt = $con->prepare("INSERT INTO tbl_news (user_id, title, description, image, category, state, district, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssssss", $user_id, $title, $description, $mediaString, $category, $state, $district, $date);

if ($stmt->execute()) {
    // If category is Breaking News, also insert into tbl_breaking_news
    $cat_lower = strtolower(trim($category));
    if ($cat_lower === 'breaking news' || trim($category) === 'ताज़ा खबर') {
        $posted_by = 'Anchor';
        $poster_id = $user_id;
        if ($user_id > 0) {
            $u_stmt = $con->query("SELECT name FROM tbl_members WHERE id = $user_id");
            if ($u_stmt && $u_stmt->num_rows > 0) {
                $u_row = $u_stmt->fetch_assoc();
                $posted_by = $u_row['name'];
            }
        }
        $is_active = 0; // Default for Anchor, needs Admin approval
        $br_stmt = $con->prepare("INSERT INTO tbl_breaking_news (title, posted_by, poster_id, is_active, created_at) VALUES (?, ?, ?, ?, ?)");
        $br_stmt->bind_param("ssiis", $title, $posted_by, $poster_id, $is_active, $date);
        $br_stmt->execute();
    }
    echo json_encode(["status" => "success", "message" => "News posted successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
}
?>
