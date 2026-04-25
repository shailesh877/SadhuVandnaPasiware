<?php
// send_group_message.php
include 'headers.php';
include 'connection.php';

$group_id = intval($_POST['group_id'] ?? 0);
$sender_id = intval($_POST['sender_id'] ?? 0);
$message = $con->real_escape_string($_POST['message'] ?? '');
$attachment = '';
$file_type = null;

// Handle file upload if any
if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $target_dir = "../uploads/chat/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        $attachment = "/uploads/chat/" . $new_filename;
        $mime = $_FILES['file']['type'];
        if (strpos($mime, 'video') !== false) $file_type = 'video';
        else if (strpos($mime, 'image') !== false) $file_type = 'image';
        else $file_type = 'document';
    }
}

// Auto-migration check: add file_type if it doesn't exist
$check_col = $con->query("SHOW COLUMNS FROM `tbl_group_messages` LIKE 'file_type'");
if ($check_col->num_rows == 0) {
    $con->query("ALTER TABLE `tbl_group_messages` ADD COLUMN `file_type` VARCHAR(50) DEFAULT NULL");
}

if (!$group_id || !$sender_id || (empty($message) && empty($attachment))) {
    echo json_encode(["status" => "error", "message" => "Group ID, Sender ID, and Message or Attachment are required"]);
    exit;
}

$sql = "INSERT INTO tbl_group_messages (group_id, sender_id, message, attachment, file_type) VALUES ($group_id, $sender_id, '$message', '$attachment', '$file_type')";

if ($con->query($sql)) {
    echo json_encode(["status" => "success", "message" => "Message sent successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to send message: " . $con->error]);
}
?>
