<?php
include 'headers.php';
include 'connection.php';

date_default_timezone_set('Asia/Kolkata');

$my       = intval($_POST['my_profile_id'] ?? 0);
$receiver = intval($_POST['receiver_id'] ?? 0);
$msg      = trim($_POST['message'] ?? '');

if(!$my || !$receiver){
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
    exit;
}

$filePath = null;
$fileType = null;

/* FILE UPLOAD */
if(isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0){

    $file = $_FILES['attachment'];

    $allowed = [
        'image/jpeg','image/png','image/webp','image/gif',
        'video/mp4','video/webm','video/quicktime'
    ];

    if(!in_array($file['type'], $allowed)){
        echo json_encode(["status" => "error", "message" => "Invalid file type"]);
        exit;
    }

    $uploadDir = __DIR__ . '/uploads/chat/';
    if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $uploadDir . $newName;

    if(!move_uploaded_file($file['tmp_name'], $dest)){
        echo json_encode(["status" => "error", "message" => "Upload failed"]);
        exit;
    }

    // browser / app accessible path
    $filePath = "/uploads/chat/" . $newName;
    $fileType = str_starts_with($file['type'], 'image') ? 'image' : 'video';
}

$date = date("Y-m-d H:i:s");

/* INSERT */
$stmt = $con->prepare("
INSERT INTO tbl_messages
(sender_id, receiver_id, message, file, file_type, created_at, seen)
VALUES (?,?,?,?,?,?,0)
");

$stmt->bind_param(
    "iissss",
    $my,
    $receiver,
    $msg,
    $filePath,
    $fileType,
    $date
);

if($stmt->execute()){
    echo json_encode([
        "status" => "success",
        "message" => "Sent",
        "file" => $filePath,
        "file_type" => $fileType
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed"]);
}
?>
